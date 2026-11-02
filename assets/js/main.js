document.addEventListener('DOMContentLoaded', function () {
    var rowsBox = document.getElementById('subject-rows');
    var template = document.getElementById('subject-row-template');
    var addBtn = document.getElementById('add-subject');
    var grandTotalOut = document.getElementById('score-total');
    var avgOut = document.getElementById('score-average');

    function recalc() {
        if (!rowsBox) return;
        
        var rows = rowsBox.querySelectorAll('.subject-row');
        var grandTotal = 0;
        var validSubjects = 0;

        rows.forEach(function (row) {
            var ca1 = parseFloat(row.querySelector('input[name="ca1[]"]').value) || 0;
            var ca2 = parseFloat(row.querySelector('input[name="ca2[]"]').value) || 0;
            var exam = parseFloat(row.querySelector('input[name="exam[]"]').value) || 0;
            var subjectName = row.querySelector('input[name="subjects[]"]').value.trim();

            var rowTotal = ca1 + ca2 + exam;
            row.querySelector('.row-total').textContent = rowTotal.toFixed(1).replace(/\.0$/, '');

            if (subjectName !== '') {
                grandTotal += rowTotal;
                validSubjects++;
            }
        });

        if (grandTotalOut) grandTotalOut.textContent = grandTotal.toFixed(2);
        if (avgOut) avgOut.textContent = validSubjects > 0 ? (grandTotal / validSubjects).toFixed(2) : '0.00';
    }

    function bindRow(row) {
        var rm = row.querySelector('.remove-row');
        if (rm) rm.addEventListener('click', function () {
            if (rowsBox.querySelectorAll('.subject-row').length > 1) { row.remove(); recalc(); }
        });
        row.querySelectorAll('input').forEach(function(input) {
            input.addEventListener('input', recalc);
        });
    }

    if (rowsBox && template && addBtn) {
        for (var i = 0; i < 5; i++) rowsBox.appendChild(template.content.cloneNode(true));
        rowsBox.querySelectorAll('.subject-row').forEach(bindRow);

        addBtn.addEventListener('click', function () {
            rowsBox.appendChild(template.content.cloneNode(true));
            var rows = rowsBox.querySelectorAll('.subject-row');
            bindRow(rows[rows.length - 1]);
        });
        recalc();
    }
});