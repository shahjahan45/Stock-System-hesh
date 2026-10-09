<?php
require dirname(__DIR__).'/includes/bootstrap.php';
require_auth();
$branch=(int)($_GET['branch_id']??0);$brand=(int)($_GET['brand_id']??0);$q=trim((string)($_GET['q']??''));
if(!$branch||!$brand)json_response(['ok'=>false,'message'=>'Choose a branch and brand.'],422);
enforce_branch_scope($branch);
$sql='SELECT p.id,p.name,p.sku,u.symbol,p.minimum_stock,COALESCE(SUM(it.quantity_in-it.quantity_out),0) available FROM products p JOIN units u ON u.id=p.unit_id LEFT JOIN inventory_transactions it ON it.product_id=p.id AND it.branch_id=? AND it.brand_id=? WHERE p.deleted_at IS NULL AND p.status="active"';
$args=[$branch,$brand];if($q!==''){$sql.=' AND (p.name LIKE ? OR p.sku LIKE ?)';$args[]='%'.$q.'%';$args[]='%'.$q.'%';}
$sql.=' GROUP BY p.id,p.name,p.sku,u.symbol,p.minimum_stock ORDER BY p.name LIMIT 100';
$s=$pdo->prepare($sql);$s->execute($args);$products=$s->fetchAll();
foreach($products as &$p){$p['available']=(float)$p['available'];$p['minimum_stock']=(float)$p['minimum_stock'];}
json_response(['ok'=>true,'products'=>$products]);
