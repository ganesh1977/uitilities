

var campaignOffer = {};

campaignOffer.init = function()
{
    // Elements
    this.dep_cd = $('#form_dep_cd');
    this.form = this.dep_cd.parents('form');
    this.stay = $('#form_stay');
    this.arr_cd = $('#form_arr_cd');
    this.st_dt  = $('#form_st_dt');
    this.end_dt = $('#form_end_dt');
    this.carr_cd = $('#form_carr_cd');
    this.carr_lbl = $('label[for="form_carr_cd"]');
    this.accom_cd = $('#form_stc_stk_cd');
    this.accom_lbl = $('label[for="form_stc_stk_cd"]');
    this.rm_cd = $('#form_rm_cd');
    this.rm_lbl = $('label[for="form_rm_cd"]');
    this.prom_cd = $('#prom_cd');
    this.promotion_cd = $('#promotion_cd');
    this.save_btn = $('#form_save');
    this.delete_btn = $('#delete_offer_button');
    this.offer_id = $("#form_id");
    this.campaign_id = $("#form_campaign");
    this.bb = $("#form_bb");


    this.oldData = this.form.serializeArray();
    this.oldAccomValue = null;
    this.oldRmValue = null;
    try {
        this.oldAccomValue = this.oldData[7].value;
    }
    catch (e) {}
    try {
        this.oldRmValue = this.oldData[8].value;
    }
    catch (e) {}

    // Add events
    var select2AirportOptions = {
        allowClear: true,
        placeholder: 'Airport IATA code',
        // selectOnClose: true,
        data: window.airports
    };

    this.arr_cd.select2(select2AirportOptions);
    this.dep_cd.select2(select2AirportOptions);

    this.onLoadBoardBasis();

    this.st_dt
        .datepicker(datepicker_options)
        .on('show', this.stopDatePickerPropagating)
        .on('hide', this.stopDatePickerPropagating)
        .on('changeDate', this.onChangeStartDate.bind(this));

    this.end_dt
        .datepicker(datepicker_options)
        .on('show', this.onShowEndDate.bind(this))
        .on('hide', this.stopDatePickerPropagating)
        .on('changeDate', this.onChangeEndDate.bind(this));

        var dt = '';
        $("#form_st_dt").keyup(function () {
            dt = $('#form_st_dt').val();
        });
        $("#form_end_dt").keyup(function () {
            dt = $('#form_end_dt').val();
        });

        $('#form_st_dt').datepicker(datepicker_options);
        $('#form_end_dt').datepicker(datepicker_options);

        $('#form_st_dt').blur(function ()
        {
            var id = '#form_st_dt';
            formatDate(id, dt, datepicker_options);
            dt = '';
        });
        $('#form_end_dt').blur(function ()
        {
            var id = '#form_end_dt';
            formatDate(id, dt, datepicker_options);
            dt = '';
        });


    this.arr_cd.on('select2:select', this.onChangeArrCd.bind(this));
    this.dep_cd.on('select2:select', this.onChangeDepCd.bind(this));

    this.stay.on('keypress keyup blur', this.onStayValidation.bind(this));
    this.stay.on('change', this.onChangeStay.bind(this));

    if (this.oldAccomValue !== null && this.oldAccomValue !== '') {
        this.disableInputs();

        this.arr_cd.trigger('select2:select');

    }

    $("#form :input").on('change', this.onChangeInput.bind(this));

    this.save_btn.on('click', this.onClickSubmit.bind(this));
    this.delete_btn.on('click', this.onClickDelete.bind(this));

    this.dep_cd.select2('focus');    
    if(this.promotion_cd.val() != 'CCI' && this.promotion_cd.val()) {
        this.st_dt.parent().addClass('required');
        this.end_dt.parent().addClass('required');
        this.stay.parent().addClass('required');
    }
};

campaignOffer.onStayValidation = function (event) {
    var item = event.target;

    $(item).val($(item).val().replace(/[^\d].+/, ""));
    if ((event.which < 48 || event.which > 57)) {
        event.preventDefault();
    }

}

/* To load data this way for making select2-tags function works */
campaignOffer.onLoadBoardBasis = function () {

    this.bb.replaceWith('<select id="' + this.bb.attr("id")+ '" name="' + this.bb.attr("name")+ '" class="form-control"></select>');

    var item_id = this.bb.attr("id");
    var new_select_item = $("#"+item_id+"");

    $.each(window.boardtypes, function(key, row) {
        new_select_item
            .append($("<option></option>")
                .attr("value",row.id)
                .text(row.text));
    });

    // For custom value
    if ($("#"+item_id+" option[value='"+this.bb.val()+"']").length == 0) {
        new_select_item
            .append($("<option></option>")
                .attr("value",this.bb.val())
                .text(this.bb.val()));

    }

    new_select_item.val(this.bb.val());

    new_select_item.select2({
        allowClear: true,
        placeholder: (this.promotion_cd.val() == 'BB') ? 'All' : 'Board type',
        tags: true,
        // selectOnClose: true,
    });

    new_select_item.on('change', this.onChangeInput.bind(this));
    this.bb = new_select_item;
    this.bb.on('select2:select', this.select2FocusFix);
    if(this.promotion_cd.val() == 'BB'){
        $('#form_bb option').remove();
    }
}

campaignOffer.onChangeInput = function (event) {
    // console.log($(event.target).attr('name'));

    this.clearErrorMessage(this.save_btn);
}

campaignOffer.onClickSubmit = function (event) {

    this.clearErrorMessage(this.save_btn);

    this.campaign_id.val(this.trimString(this.campaign_id.val()));
    this.st_dt.val(this.trimString(this.st_dt.val()));
    this.end_dt.val(this.trimString(this.end_dt.val()));
    this.dep_cd.val(this.trimString(this.dep_cd.val()));
    this.arr_cd.val(this.trimString(this.arr_cd.val()));
    this.stay.val(this.trimString(this.stay.val()));
    this.accom_cd.val(this.trimString(this.accom_cd.val()));

    // Check required fileds
    if (this.campaign_id.val() === ""
        || this.st_dt.val() === ""
        || this.end_dt.val() === ""
        || this.dep_cd.val() === ""
        || this.arr_cd.val() === ""
        || this.stay.val() === ""
        || this.accom_cd.val() === ""
    )
    {
        $('input:text[required]').parent().show();

        return;
    }

    if (/\D/g.test(this.stay.val())) {
        this.showErrorMessage(this.stay, 'This value must be numerical only');

        return;
    }

    event.preventDefault();

    this.save_btn.prop('disabled', true);
    this.save_btn.addClass('loading');
    var datastring = this.form.serialize();

    $.ajax({
        url: '/utils/dev/campaign/offer/edit/validation?offer_id=' + this.offer_id.val(),
        data: datastring,
        type: 'get',
        dataType: "json",
        success: this.onLoadMessage.bind(this),
        error: function(xhr, textStatus, error) {
            console.log(xhr, textStatus, xhr.responseJSON.error);
        }.bind(this)
    });
}

campaignOffer.onClickDelete = function (event) {

    if (confirm("Are you sure to delete this offer?")) {
        $.ajax({
            url: '/utils/dev/campaign/offer/delete',
            data: JSON.stringify({offer_id : this.offer_id.val()}),
            type: 'post',
            dataType: "json",
            success: function (res) {
                location.reload();
            },
            error: function(xhr, textStatus, error) {
                this.clearErrorMessage(this.save_btn);
                this.save_btn.before('<div class="form-control-feedback text-danger"><small>' + xhr.responseJSON.error + '</small></div>');

            }.bind(this)
        });
    }
}

campaignOffer.onLoadMessage = function (response) {
    if (response.success) {
        this.form.submit();
    }
    else {
        var messages = jQuery.parseJSON(response.messages);

        if (messages.duplicated !== undefined) {
            this.showErrorMessage(this.save_btn, "&nbsp;&nbsp;" + messages.duplicated);
        }

        this.save_btn.removeClass('loading');
        this.save_btn.prop('disabled', true);

    }
}

campaignOffer.removeEvents = function()
{
    try {
        this.dep_cd.off();
        this.arr_cd.off();
        this.st_dt.off();
        this.end_dt.off();
        this.accom_cd.off();
        this.rm_cd.off();
        this.rm_lbl.off();
        this.prom_cd.off();
        this.stay.off();
        this.bb.off();
    }
    catch (e) {}
};

campaignOffer.onChangeDepCd = function (event) {

    this.clearErrorMessage($(event.target));
    this.clearErrorMessage(this.arr_cd);

    if (event.target === this.dep_cd[0]) {
        this.select2FocusFix(event);
    }

    if ($(event.target).val() === this.arr_cd.val()) {
        var message = "Airports must be different.";
        this.showErrorMessage($(event.target), message);

        return;
    }

    if (this.arr_cd.val() !== '') {
        this.arr_cd.trigger('select2:select');
    }
    this.getCarrierCodes(event);
}

campaignOffer.onChangeArrCd = function (event) {
    this.getCarrierCodes(event);
    this.getAccoms(event);
}

campaignOffer.getCarrierCodes = function (event) {
    this.clearErrorMessage($(event.target));
    this.clearErrorMessage(this.dep_cd);
    this.clearErrorMessage(this.accom_cd);

    if (event.target == this.arr_cd[0]) {
        this.select2FocusFix(event);
    }
    
    if (this.arr_cd.val() == '') {
        this.enableInputs();
        return;
    }

    this.enableLoadingAnimation(false, this.arr_cd.val(), true);
    this.arr_cd.select2('enable', false);
    this.dep_cd.select2('enable', false);
    this.save_btn.prop('disabled', true);

    $.ajax({
        url: '/utils/dev/campaign/offers/flt_carrier',
        data: {
            dep_cd: this.dep_cd.val(),
            arr_cd: this.arr_cd.val(),
            st_dt: this.st_dt.val(),
            end_dt: this.end_dt.val(),
            stay: this.stay.val(),
            prom_cd: this.prom_cd.val(),
            promotion_cd: this.promotion_cd.val()
        },
        type: 'get',
        dataType: 'json',
        success: this.onLoadCarriers.bind(this),
        error: function(xhr, textStatus, error) {
            this.disableLoadingAnimation(true, xhr.responseJSON.arr_cd);

            alert(xhr.responseJSON.error);
            // console.log(xhr, textStatus, error);
        }.bind(this)
    })
}

campaignOffer.onLoadCarriers = function (response)
{
    this.disableLoadingAnimation(false, response.arr_cd, true);
    this.save_btn.prop('disabled', false);

    if (response.carriers == 0) {
        this.arr_cd.select2('enable');
        this.dep_cd.select2('enable');

        this.clearErrorMessage(this.arr_cd);
        this.showErrorMessage(this.arr_cd, 'No carriers found.');
        return;
    }
    else {
        this.clearErrorMessage(this.arr_cd);
    }

    this.carr_cd
        .select2({
            allowClear: true,
            placeholder: 'Choose carriers between ' + this.dep_cd.val() + ' and ' + this.arr_cd.val(),
            data: response.carriers
        });
}


campaignOffer.getAccoms = function(event)
{
    this.clearErrorMessage($(event.target));
    this.clearErrorMessage(this.dep_cd);
    this.clearErrorMessage(this.accom_cd);

    if (event.target == this.arr_cd[0]) {
        this.select2FocusFix(event);
    }

    if ($(event.target).val() === this.dep_cd.val()) {
        var message = "Airports must be different.";
        this.showErrorMessage($(event.target), message);

        return;
    }

    var value = this.arr_cd.val();
    var promotionCd = this.promotion_cd.val();
    
    if (value == '') {
        this.enableInputs();
        return;
    }

    this.enableLoadingAnimation(false, value);
    this.arr_cd.select2('enable', false);
    this.dep_cd.select2('enable', false);
    this.save_btn.prop('disabled', true);

    $.ajax({
        url: '/utils/dev/campaign/offers/accoms',
        data: {
            arr_cd: value,
            prom_cd: this.prom_cd.val(),
            stay: this.stay.val(),
            st_dt: this.st_dt.val(),
            end_dt: this.end_dt.val(),
            promotion_cd: promotionCd
        },
        type: 'get',
        dataType: 'json',
        success: this.onLoadAccoms.bind(this),
        error: function(xhr, textStatus, error) {
            this.disableLoadingAnimation(true, xhr.responseJSON.arr_cd);

            alert(xhr.responseJSON.error);
            // console.log(xhr, textStatus, error);
        }.bind(this)
    })
};


campaignOffer.onLoadAccoms = function(response)
{
    this.disableLoadingAnimation(false, response.arr_cd);
    this.save_btn.prop('disabled', false);

    // Won't work on first run because it's not initialized as a select2 object.
    try {
        if (this.accom_cd.next().find('.select2-selection').length > 0) {
            this.accom_cd
                .val('')
                .off()
                .select2('destroy');
        }
    }
    catch (e) {}

    // Won't work on first run because it's not initialized as a select2 object.
    try {
        if (this.rm_cd.next().find('.select2-selection').length > 0) {
            this.rm_cd
                .val('')
                .off()
                .select2('destroy');

        }

        this.rm_cd.empty();

    }
    catch (e){}

    if (response.hotels == 0) {
        this.arr_cd.select2('enable');
        this.dep_cd.select2('enable');

        this.clearErrorMessage(this.arr_cd);
        this.showErrorMessage(this.arr_cd, 'No hotels found.');

        // return alert('No hotels found!');
        return;
    }
    else {
        this.clearErrorMessage(this.arr_cd);
    }

    if (this.oldAccomValue !== null && this.oldAccomValue !== '') {
        this.accom_cd.val(this.oldAccomValue);
    }
    else {
        this.arr_cd.select2('enable');
        this.dep_cd.select2('enable');
    }

    this.accom_cd
        .select2({
            allowClear: true,
            placeholder: 'Accom. in '+ response.arr_cd,
            // selectOnClose: true,
            data: response.hotels
        })
        .on('select2:select', function(event)
        {
            this.getAccomRooms(event);
            if (event.target.value !== '') {
                this.select2FocusFix(event);
            }

        }.bind(this));


    if (this.oldAccomValue !== null && this.oldAccomValue !== "") {
        this.oldAccomValue = null;
        this.accom_cd.trigger('select2:select');

        this.accom_cd.select2('enable', false);
    }
};


campaignOffer.getAccomRooms = function(event)
{
    // console.log("getAccomRooms : " + event.target.id);

    this.clearErrorMessage(this.accom_cd);

    if (this.accom_cd.val() == '' || this.st_dt.val() == '' || this.end_dt.val() == '') {
        return;
    }

    if (this.st_dt.val() == this.end_dt.val()) {
        return;
    }

    var conn_cd = this.hashCode(this.accom_cd.val() + this.st_dt.val() + this.end_dt.val() + this.stay.val() + new Date());

    this.enableLoadingAnimation(true, conn_cd);
    this.accom_cd.select2('enable', false);
    this.arr_cd.select2('enable', false);
    this.dep_cd.select2('enable', false);
    this.save_btn.prop('disabled', true);

    $.ajax({
        url: '/utils/dev/campaign/offers/accom/rooms',
        data: {
            accom_cd: this.accom_cd.val(),
            arr_cd: this.arr_cd.val(),
            prom_cd: this.prom_cd.val(),
            stay: this.stay.val(),
            st_dt: this.st_dt.val(),
            end_dt: this.end_dt.val(),
            conn_cd: conn_cd
        },
        dataType: 'json',
        type: 'get',
        success: this.onLoadAccomRooms.bind(this),
        error: function(xhr, textStatus, error) {
            try {
                this.disableLoadingAnimation(true, xhr.responseJSON.conn_cd);
            }
            catch (e) {}

            this.accom_cd.select2('enable');
            this.arr_cd.select2('enable');
            this.dep_cd.select2('enable');

            try {
                // alert(xhr.responseJSON.error);
                this.clearErrorMessage(this.accom_cd);
                this.showErrorMessage(this.accom_cd, xhr.responseJSON.error);

                // console.log(xhr, textStatus, error);
            }
            catch (e) {}
        }.bind(this)
    });
};


campaignOffer.onLoadAccomRooms = function(response)
{
    // Won't work on first run because it's not initialized as a select2 object.
    try {
        if (this.rm_cd.next().find('.select2-selection').length > 0) {
            this.rm_cd
                .val('')
                .off()
                .select2('destroy');
        }
    }
    catch (e){}

    if (this.oldRmValue !== null) {
        this.rm_cd.val(this.oldRmValue);
        this.oldRmValue = null;

        this.enableInputs();
    }

    // Add custom text option
    this.rm_cd.replaceWith('<select id="' + this.rm_cd.attr("id")+ '" name="' + this.rm_cd.attr("name")+ '" class="form-control" disabled></select>');

    var rm_cd_id = this.rm_cd.attr("id");
    var rm_cd_item = $("#"+rm_cd_id+"");

    $.each(response.rooms, function(key, row) {
        rm_cd_item
            .append($("<option></option>")
                .attr("value", row.room_cd)
                .text(row.text +' ('+ row.units +' units)'));
    });

    if ($("#"+rm_cd_id+" option[value='"+this.rm_cd.val()+"']").length == 0) {
        rm_cd_item
            .append($("<option></option>")
                .attr("value",this.rm_cd.val())
                .text(this.rm_cd.val()));

        // For keeping the current room code
        // this.oldRmValue = this.rm_cd.val();
    }

    rm_cd_item.val(this.rm_cd.val());

    rm_cd_item.select2({
        allowClear: true,
        placeholder: (this.promotion_cd.val() == 'BB') ? 'All' : 'Rooms in '+ response.accom_cd,
        tags: true,
        // selectOnClose: true,
    });

    rm_cd_item.on('change', this.onChangeInput.bind(this));
    this.rm_cd = rm_cd_item;
    this.rm_cd.on('select2:select', this.select2FocusFix);

    try {
        this.disableLoadingAnimation(false, response.conn_cd);
        this.save_btn.prop('disabled', false);
    }
    catch (e){}
    
    if(this.promotion_cd.val() == 'BB'){
        this.rm_cd.prop('disabled', true);
        this.bb.prop('disabled', true);
    }
};

campaignOffer.onShowEndDate = function (event) {

    // Set start date for endDate
    // var stDate = new Date(this.st_dt.datepicker('getDate'));
    // stDate.setDate(stDate.getDate() + 1);
    // this.end_dt.datepicker('setStartDate', stDate);

    this.stopDatePickerPropagating(event);

}

campaignOffer.onChangeStartDate = function (event)
{
    this.clearErrorMessage($(event.target));

    var date = event.date;

    if (event.target == this.st_dt[0] && this.end_dt.val() !== '' && this.st_dt.datepicker('getDate') < this.end_dt.datepicker('getDate')) {
        if(this.promotion_cd.val() != 'CCI' && this.promotion_cd.val()){ //only check for carrier codes when promotion is not CCI
            this.getCarrierCodes(event);
        }
        this.getAccomRooms(event);
        return;
    }

    if (this.end_dt.val() !== "" && this.st_dt.val() === this.end_dt.val()) {
        var message = "Start date must be before end date.";
        this.showErrorMessage($(event.target), message);

        return;
    }

    date.setDate(date.getDate() + 7);
    this.end_dt.datepicker('setDate', date);


};

campaignOffer.onChangeEndDate = function (event) {

    this.clearErrorMessage($(event.target));
    this.clearErrorMessage(this.st_dt);

    if (this.st_dt.val() !== "" && this.st_dt.val() === $(event.target).val()) {
        var message = "End date must be after start date.";
        this.showErrorMessage($(event.target), message);

        return;
    }
    if(this.promotion_cd.val() == 'CCI' || this.promotion_cd.val() == '') {
        this.getAccomRooms(event);
    }
    else if(this.promotion_cd.val() == 'DP'){
        this.getCarrierCodes(event);
        this.getAccomRooms(event);
    }
    else {
        this.getCarrierCodes(event);
        this.getAccoms(event);
    }
}

campaignOffer.onChangeStay = function (event) {
    if(this.promotion_cd.val() == 'CCI' || this.promotion_cd.val() == '') {
        this.getAccomRooms(event);
    }
    else if(this.promotion_cd.val() == 'DP'){
        this.getCarrierCodes(event);
        this.getAccomRooms(event);
    }
    else {
        this.getCarrierCodes(event);
        this.getAccoms(event);
    }
}


campaignOffer.select2FocusFix = function(event)
{
    var select2Element = $(event.target).next();
    select2Element.find('.select2-selection')[0].focus();
};

campaignOffer.stopDatePickerPropagating = function (event)
{
    try { event.stopPropagation(); }
    catch (e) {}
};

campaignOffer.enableLoadingAnimation = function(onlyRoom, cd, onlyCarr = false)
{
    if(onlyCarr == false){
        this.rm_lbl.addClass('loading');
        this.rm_lbl.addClass('cd-' + cd)
        this.rm_cd.prop('disabled', true);
    }

    if (typeof onlyRoom != typeof undefined && onlyRoom === true) {
        return;
    }
    
    if(onlyCarr == true){
        this.carr_lbl.addClass('loading');
        this.carr_lbl.addClass('cd-' + cd);
        this.carr_cd.prop('disabled', true);
    }
    else {
        this.accom_lbl.addClass('loading');
        this.accom_lbl.addClass('cd-' + cd);
        this.accom_cd.prop('disabled', true);
    }

    this.arr_cd.select2('enable', false);
    this.dep_cd.select2('enable', false);

};

campaignOffer.disableLoadingAnimation = function(forceDisable, cd, onlyCarr = false)
{
    var classes = this.rm_lbl.attr('class').split(' ');
    var cdClass = new Array();
    var idx = 0;
    for (var i = 0; i < classes.length; i++) {
        var matches = /^cd\-(.+)/.exec(classes[i]);
        if (matches != null) {
            cdClass[idx++] = matches[1];
        }
    }

    if (cdClass.length > 0) {
        this.rm_lbl.removeClass('cd-' + cd);
        this.carr_lbl.removeClass('cd-' + cd);
        this.accom_lbl.removeClass('cd-' + cd);

        var index = cdClass.indexOf(cd);
        cdClass.splice(index, 1);
    }

    if (cdClass.length == 0 || (typeof forceDisable != typeof undefined && forceDisable === true) || onlyCarr == true) {
        if(onlyCarr == true) {
            this.carr_lbl.removeClass('loading');
            this.carr_cd.prop('disabled', false);
        }
        else {
            this.accom_lbl.removeClass('loading');
            this.accom_cd.prop('disabled', false);
            
            this.rm_lbl.removeClass('loading');
            this.rm_cd.prop('disabled', false);
        }
        
        this.arr_cd.select2('enable');
        this.dep_cd.select2('enable');

        if (this.rm_cd.next().find('.select2-selection').length > 0) {
            this.rm_cd.select2('enable');
        }

    }

}

campaignOffer.disableInputs = function () {
    this.form.find(':input').prop('disabled', true);

    this.dep_cd.select2('enable', false);
    this.arr_cd.select2('enable', false);
    this.save_btn.prop('disabled', true);
    this.delete_btn.addClass('disabled');
}

campaignOffer.enableInputs = function () {

    var disabled = this.dep_cd.prop('disabled');

    this.form.find(':input').prop('disabled', false);
    this.dep_cd.select2('enable');
    this.arr_cd.select2('enable');
    this.save_btn.prop('disabled', false);
    this.delete_btn.removeClass('disabled');

    if (disabled) {
        this.dep_cd.focus();
    }


}

campaignOffer.clearErrorMessage = function (item) {

    var hasError = item.parent().hasClass('has-danger');

    item.parent().removeClass('has-danger');
    item.parent().find('.form-control-feedback').remove();

    if (hasError && this.form.find('.has-danger').length == 0) {
        this.save_btn.prop('disabled', false);
    }
}

campaignOffer.showErrorMessage = function (item, message) {
    item.parent().addClass('has-danger');

    var element = item.next().length > 0 ? item.next() : item;
    element.after('<span class="form-control-feedback"><small>' + message + '</small></span>');

    element.focus();

    this.save_btn.prop('disabled', true);
}

campaignOffer.hashCode = function(str) {
    var hash = 0;
    if (str.length == 0) return hash;
    for (i = 0; i < str.length; i++) {
        char = str.charCodeAt(i);
        hash = ((hash<<5)-hash)+char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return hash;
}

campaignOffer.trimString = function (str) {
    return str.replace(/^\s+|\s+$/g, "")
}
