var campaign = {};

campaign.init = function () {

    this.form = $("form[name='form']");
    this.st_dt  = $('#form_st_dt');
    this.end_dt = $('#form_end_dt');
    this.cd = $("#form_cd");
    this.prom_cd = $('#form_prom_cd');
    this.desc = $('#form_description');
    this.save_btn = $("#form_save");
    this.campaign_id = $("#form_id");
    this.delete_btn = $("#delete_campaign_button");

    this.st_dt
        .datepicker(datepicker_options)
        .on('show', this.stopDatePickerPropagating)
        .on('hide', this.stopDatePickerPropagating)
        .on('changeDate', this.onChangeStartDate.bind(this));

    this.end_dt
        .datepicker(datepicker_options)
        .on('show', this.stopDatePickerPropagating)
        .on('hide', this.stopDatePickerPropagating)
        .on('changeDate', this.onChangeEndDate.bind(this));

	this.prom_cd.on('change', this.onChangePromCd.bind(this));
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


    this.cd.on('change', this.onChangeCampaignCode.bind(this));

    this.save_btn.on('click', this.onClickSubmit.bind(this));
    this.delete_btn.on('click', this.onClickDelete.bind(this));

    this.prom_cd.focus();
};

campaign.onChangeCampaignCode = function (event) {

    this.clearErrorMessage($(event.target));
};

campaign.stopDatePickerPropagating = function (event)
{
    try { event.stopPropagation(); }
    catch (e) {}
};

campaign.onClickSubmit = function (event) {

    this.cd.val(this.trimString(this.cd.val()));
    this.st_dt.val(this.trimString(this.st_dt.val()));
    this.end_dt.val(this.trimString(this.end_dt.val()));

    if (this.cd.val() === "" || this.st_dt.val() === "" || this.end_dt.val() === "") {
        $('input:text[required]').parent().show();

        return;
    }

    event.preventDefault();

    this.save_btn.prop('disabled', true);
    this.save_btn.addClass('loading');
    var datastring = this.form.serialize();

    $.ajax({
        url: '/utils/dev/campaign/edit/validation?campaign_id=' + this.campaign_id.val(),
        data: datastring,
        type: 'get',
        dataType: "json",
        success: this.onLoadMessage.bind(this)
    });
};

campaign.onClickDelete = function (event) {

    if (confirm("Are you sure to delete this campaign?")) {
        $.ajax({
            url: '/utils/dev/campaign/delete',
            data: JSON.stringify({campaign_id : this.campaign_id.val()}),
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

campaign.onLoadMessage = function (response) {
    if (response.success) {
        this.form.submit();
    }
    else {
        if (response.messages.hasOwnProperty('cd')) {
            this.showErrorMessage(this.cd, response.messages.cd);
        }

        this.save_btn.removeClass('loading');
    }
};

campaign.showErrorMessage = function (item, message) {
    item.parent().addClass('has-danger');

    item.after('<span class="form-control-feedback"><small>' + message + '</small></span>');

    this.save_btn.prop('disabled', true);

};

campaign.clearErrorMessage = function (item)
{
    item.parent().removeClass('has-danger');
    item.parent().find('.form-control-feedback').remove();

    if (this.form.has('.has-danger')) {
        this.save_btn.prop('disabled', false);
    }
};

campaign.onChangePromCd = function (event)
{
    var prom_cd = $(event.target).val();
    var bbProm = ["LM","BT","SR","SO","ST"];
    var inArrProm = $.inArray(prom_cd, bbProm);
    
    if(inArrProm < 0){
        $("option[value='BB']").remove();
    }
    else if($("option[value='BB']").length == 0){
        $("#form_promotion_cd").append(new Option("Bedbank", "BB"));
    }
}

campaign.onChangeStartDate = function (event)
{
    this.clearErrorMessage($(event.target));

    var date = event.date;
    // date.setDate(date.getDate() + 1);
    // this.end_dt.datepicker('setStartDate', date);

    if (event.target === this.st_dt[0] && this.end_dt.val() !== '' && this.st_dt.datepicker('getDate') < this.end_dt.datepicker('getDate')) {
        return;
    }

    if (this.end_dt.val() !== "" && this.st_dt.val() === this.end_dt.val()) {
        var message = "End date must be after start date.";
        this.showErrorMessage($(event.target), message);
        return;
    }

    date.setMonth(date.getMonth() + 1);
    this.end_dt.datepicker('setDate', date);
};

campaign.onChangeEndDate = function (event)
{
    this.clearErrorMessage($(event.target));

    if (this.st_dt.val() !== "" && this.st_dt.val() === $(event.target).val()) {
        var message = "End date must be after start date.";
        this.showErrorMessage($(event.target), message);
    }
};

campaign.trimString = function (str) {
    return str.replace(/^\s+|\s+$/g, "")
};
