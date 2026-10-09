<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_auth();
require __DIR__.'/includes/layout.php';
require __DIR__.'/includes/data.php';

$timezone=new DateTimeZone($config['app']['timezone']);
$currentMonth=(new DateTimeImmutable('now',$timezone))->format('Y-m');
$month=(string)($_GET['month']??$currentMonth);
$selected=DateTimeImmutable::createFromFormat('!Y-m',$month,$timezone);
if(!$selected||$selected->format('Y-m')!==$month||$month>$currentMonth){
    $month=$currentMonth;
    $selected=DateTimeImmutable::createFromFormat('!Y-m',$month,$timezone);
}
$from=$selected->format('Y-m-01');
$to=$selected->format('Y-m-t');
$branch=(int)($_GET['branch']??0);
$brand=(int)($_GET['brand']??0);
if((int)(user()['branch_id']??0)&&(user()['role_slug']??'')!=='super-admin')$branch=(int)user()['branch_id'];

$where=['s.sale_date BETWEEN ? AND ?','s.status="completed"','s.deleted_at IS NULL'];
$args=[$from,$to];
if($branch){$where[]='s.branch_id=?';$args[]=$branch;}
if($brand){$where[]='s.brand_id=?';$args[]=$brand;}
$statement=$pdo->prepare('SELECT s.reference_no,s.sale_date,s.total_amount,s.cash_amount,s.card_amount,s.talabat_amount,s.keeta_amount,s.snoonu_amount,s.notes,b.name branch,br.name brand,u.name entered_by FROM sales s JOIN branches b ON b.id=s.branch_id JOIN brands br ON br.id=s.brand_id JOIN users u ON u.id=s.created_by WHERE '.implode(' AND ',$where).' ORDER BY s.sale_date DESC,s.id DESC');
$statement->execute($args);
$rows=$statement->fetchAll();
$totals=['total'=>0.0,'cash'=>0.0,'card'=>0.0,'talabat'=>0.0,'keeta'=>0.0,'snoonu'=>0.0];
foreach($rows as $row){
    $totals['total']+=(float)$row['total_amount'];
    foreach(['cash','card','talabat','keeta','snoonu'] as $channel)$totals[$channel]+=(float)$row[$channel.'_amount'];
}

if(($_GET['export']??'')==='csv'){
    require_permission('reports.export');
    audit($pdo,'export','monthly_sales',null,null,['month'=>$month,'branch'=>$branch,'brand'=>$brand,'records'=>count($rows)]);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="monthly-sales-'.$month.'.csv"');
    header('X-Content-Type-Options: nosniff');
    $out=fopen('php://output','w');
    fwrite($out,"\xEF\xBB\xBF");
    $safe=static fn($value)=>is_string($value)&&preg_match('/^[=+\-@]/',$value)?"'".$value:$value;
    fputcsv($out,['Month','Date','Reference','Branch','Brand','Total Sales','Cash','Card','Talabat','Keeta','Snoonu','Entered By','Notes']);
    foreach($rows as $row)fputcsv($out,array_map($safe,[$selected->format('F Y'),$row['sale_date'],$row['reference_no'],$row['branch'],$row['brand'],$row['total_amount'],$row['cash_amount'],$row['card_amount'],$row['talabat_amount'],$row['keeta_amount'],$row['snoonu_amount'],$row['entered_by'],$row['notes']]));
    fclose($out);
    exit;
}

$branches=options($pdo,'branches');
$brands=options($pdo,'brands');
$previous=$selected->modify('-1 month')->format('Y-m');
$next=$selected->modify('+1 month')->format('Y-m');
$queryBase=['month'=>$month,'branch'=>$branch,'brand'=>$brand];
$exportQuery=http_build_query(array_merge($queryBase,['export'=>'csv']));
render_header('Monthly Sales','monthly-sales');
?>
<div class="page-head monthly-report-head"><div><span class="eyebrow">SALES HISTORY</span><h1>Monthly Sales Report</h1><p>Completed sales for <?=e($selected->format('F Y'))?> · Historical records remain unchanged.</p></div><div class="page-head-actions"><?php if(can('reports.export')):?><a class="btn" href="?<?=e($exportQuery)?>"><i data-icon="download"></i>Export CSV</a><?php endif;?><button type="button" class="btn" onclick="print()"><i data-icon="printer"></i>Print / PDF</button><a class="btn primary" href="<?=app_url('sales.php?action=create')?>"><i data-icon="plus"></i>Add Daily Sales</a></div></div>

<article class="card month-selector-card"><a class="month-step" href="?<?=e(http_build_query(array_merge($queryBase,['month'=>$previous])))?>" aria-label="Previous month">←</a><form method="get" class="month-selector"><label>Month and year<input type="month" name="month" value="<?=e($month)?>" max="<?=e($currentMonth)?>"></label><label>Branch<select name="branch"<?=((int)(user()['branch_id']??0)&&(user()['role_slug']??'')!=='super-admin')?' disabled':''?>><option value="0">All Branches</option><?php foreach($branches as $option):?><option value="<?=$option['id']?>"<?=selected($branch,$option['id'])?>><?=e($option['label'])?></option><?php endforeach;?></select></label><?php if((int)(user()['branch_id']??0)&&(user()['role_slug']??'')!=='super-admin'):?><input type="hidden" name="branch" value="<?=$branch?>"><?php endif;?><label>Brand<select name="brand"><option value="0">All Brands</option><?php foreach($brands as $option):?><option value="<?=$option['id']?>"<?=selected($brand,$option['id'])?>><?=e($option['label'])?></option><?php endforeach;?></select></label><button class="btn primary">View Report</button></form><?php if($next<=$currentMonth):?><a class="month-step" href="?<?=e(http_build_query(array_merge($queryBase,['month'=>$next])))?>" aria-label="Next month">→</a><?php else:?><span class="month-step disabled" aria-hidden="true">→</span><?php endif;?></article>

<div class="sales-kpis monthly-kpis"><article><span>Total Sales</span><b><?=money($totals['total'])?></b><small><?=e($selected->format('F Y'))?> · completed only</small></article><article><span>Cash</span><b><?=money($totals['cash'])?></b><small><?=count($rows)?> closing <?=count($rows)===1?'entry':'entries'?></small></article><article><span>Card</span><b><?=money($totals['card'])?></b></article><article><span>Delivery Apps</span><b><?=money($totals['talabat']+$totals['keeta']+$totals['snoonu'])?></b><small>Talabat + Keeta + Snoonu</small></article></div>

<article class="card channel-breakdown"><div><span>Talabat</span><b><?=money($totals['talabat'])?></b></div><div><span>Keeta</span><b><?=money($totals['keeta'])?></b></div><div><span>Snoonu</span><b><?=money($totals['snoonu'])?></b></div><div><span>Report Period</span><b><?=date('d M',strtotime($from))?> – <?=date('d M Y',strtotime($to))?></b></div></article>

<article class="card table-card monthly-sales-table"><div class="table-toolbar"><div><b><?=e($selected->format('F Y'))?> transactions</b><small><?=count($rows)?> completed daily sales <?=count($rows)===1?'record':'records'?></small></div><input placeholder="Search monthly sales…" data-table-search></div><div class="table-wrap"><table><thead><tr><th>Date / Reference</th><th>Branch / Brand</th><th>Cash</th><th>Card</th><th>Talabat</th><th>Keeta</th><th>Snoonu</th><th>Total</th><th>Entered By</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td data-label="Date / Reference"><b><?=date('d M Y',strtotime($row['sale_date']))?></b><small><?=e($row['reference_no'])?></small></td><td data-label="Branch / Brand"><?=e($row['branch'])?> · <?=e($row['brand'])?></td><td data-label="Cash"><?=money($row['cash_amount'])?></td><td data-label="Card"><?=money($row['card_amount'])?></td><td data-label="Talabat"><?=money($row['talabat_amount'])?></td><td data-label="Keeta"><?=money($row['keeta_amount'])?></td><td data-label="Snoonu"><?=money($row['snoonu_amount'])?></td><td data-label="Total"><b><?=money($row['total_amount'])?></b></td><td data-label="Entered By"><?=e($row['entered_by'])?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="9"><div class="empty"><div class="empty-icon"><i data-icon="receipt"></i></div><b>No completed sales for <?=e($selected->format('F Y'))?></b><small>This month correctly starts at <?=money(0)?> until sales are recorded.</small></div></td></tr><?php endif;?></tbody></table></div></article>
<?php render_footer('monthly-sales');
