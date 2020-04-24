/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var datepicker_options = {
    format: 'dd-M-yyyy',
    autoclose: true,
    weekStart: 1
};

function formatDate(id, dt, dto) {
    var pdate = '';
    var format = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
    //Checking for special characters in the input field
    if (!dt || format.test(dt)) {
        $(id).datepicker(dto);
    } else
    {
        var res = dt.split("");

        if (res.length == 6) {
            pdate = (res[0] + res[1]) + '-' + (res[2] + res[3]) + '-' + (res[4] + res[5]);
            if (res[4] + res[5] >= 70 && res[4] + res[5] <= 99) {
                pdate = (res[0] + res[1]) + '-' + (res[2] + res[3]) + '-' + '19' + (res[4] + res[5]);
            } else {
                pdate = (res[0] + res[1]) + '-' + (res[2] + res[3]) + '-' + '20' + (res[4] + res[5]);
            }
        }
        $(id).datepicker('update', pdate);
    }
}