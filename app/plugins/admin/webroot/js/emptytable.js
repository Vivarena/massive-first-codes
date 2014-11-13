function printEmpty(context) {
    var $tbl = context.find('table.contact-info'),
        $trs = $tbl.find('tr');
    if ($trs.length == 1) {
        $('<tr></tr>', {'id':'NoItems'}).append($('<td></td>', {
            'colspan':$trs.first().children('td').length,
            'class':'even',
            'text':'No items available. Please add new.'
        })).appendTo($tbl);
    }
}

function deleteEmpty(table) {
    var empty = table.find('#NoItems');
    if (empty.length) {
        empty.remove();
    }
}