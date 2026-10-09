'use strict';

const assert = require('node:assert/strict');

const sales = [
  {date:'2026-09-30', amount:90, status:'completed', deleted:false},
  {date:'2026-10-01', amount:100, status:'completed', deleted:false},
  {date:'2026-10-09', amount:250, status:'completed', deleted:false},
  {date:'2026-10-09', amount:900, status:'cancelled', deleted:false},
  {date:'2026-10-08', amount:500, status:'draft', deleted:false},
  {date:'2026-10-07', amount:700, status:'completed', deleted:true},
  {date:'2026-11-02', amount:75, status:'completed', deleted:false},
  {date:'2024-02-29', amount:29, status:'completed', deleted:false}
];

function completedTotal(from, to) {
  return sales
    .filter(row => row.date >= from && row.date <= to && row.status === 'completed' && !row.deleted)
    .reduce((total, row) => total + row.amount, 0);
}

assert.equal(completedTotal('2026-10-01','2026-10-09'),350,'month-to-date accumulates completed October sales only');
assert.equal(completedTotal('2026-12-01','2026-12-31'),0,'a new month without sales starts at zero');
assert.equal(completedTotal('2026-11-01','2026-11-30'),75,'the next month excludes previous-month sales');
assert.equal(completedTotal('2026-09-01','2026-09-30'),90,'previous-month history remains independently reportable');
assert.equal(completedTotal('2024-02-01','2024-02-29'),29,'leap-day sales are included in February');

console.log('Monthly sales boundary and status scenarios: passed');
