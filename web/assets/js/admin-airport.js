var airport = {};

airport.init = function()
{
    this.deleteButton =  $('button[name="delete_airport"]');
    this.editButton =  $('button[name="edit_airport"]');
    this.addButton =  $('button[name="new_airport"]');
    this.countryName = $("#airport_countryName");
    this.countryCode = $("#airport_countryCode");
    this.code = $("#airport_code");
    this.orgCode = this.code.val();
    this.name = $("#airport_name");
    this.cityCode = $("#airport_cityCode");
    this.cityName = $("#airport_cityName");

    this.form = this.countryCode.parents('form');

    this.deleteButton.on('click', this.onDeleteButtonClicked.bind(this));
    this.editButton.on('click', this.onEditButtonClicked.bind(this));
    this.addButton.on('click', this.onAddButtonClicked.bind(this));


    var select2CountryOptions = {
        allowClear: true,
        placeholder: 'Country code - name',
        data: window.countries
    };

    this.countryCode.select2(select2CountryOptions);
    this.countryCode.on('select2:select', this.onCountryCodeSelected.bind(this));
}

airport.onDeleteButtonClicked = function (event) {
    var airport_code = $(event.target).data('id');

    if (confirm("Are you sure to delete this airport?")) {

        this.deleteButton.prop('disabled', true);
        this.deleteButton.addClass('loading');

        $.ajax({
            url: '/admin/airport/delete',
            type: 'POST',
            data: JSON.stringify({code : airport_code}),
            dataType: "json",
            success: function (res) {
                location.reload();
            },
            error: function(xhr, textStatus, error) {
                alert(xhr.responseJSON.error);
            }.bind(this)
        });
    }
}

airport.onAddButtonClicked = function (event) {

    $('.has-danger').removeClass('has-danger');
    this.form.find('.form-control-feedback').remove();

    if (this.hasRequiredField()) return;

    var item = event.target;
    var countryCode = this.code.val();
    var datastring = this.form.serialize();

    this.addButton.prop('disabled', true);
    this.addButton.addClass('loading');

    $.ajax({
        url: '/admin/airport/new',
        data: datastring,
        type: 'post',
        dataType: 'json',
        success: function () {
            location.reload();
        },
        error: function(xhr) {

            this.addButton.prop('disabled', false);
            this.addButton.removeClass('loading');

            var errors = xhr.responseJSON.errors;

            $.each( errors, function( index, value ){
                var inputName = index;
                var inputError = value[0];

                this.showErrorMessage($("#airport_"+inputName), inputError);
            }.bind(this));



        }.bind(this)
    })
}

airport.onEditButtonClicked = function (event) {

    $('.has-danger').removeClass('has-danger');
    this.form.find('.form-control-feedback').remove();

    if (this.hasRequiredField()) return;

    var item = event.target;
    var countryCode = this.orgCode;
    var datastring = this.form.serialize();

    this.editButton.prop('disabled', true);
    this.editButton.addClass('loading');

    $.ajax({
        url: '/admin/airport/' + countryCode + '/edit',
        data: datastring,
        type: 'post',
        dataType: 'json',
        success: function () {
            location.reload();
        },
        error: function(xhr) {

            this.editButton.prop('disabled', false);
            this.editButton.removeClass('loading');

            var errors = xhr.responseJSON.errors;

            $.each( errors, function( index, value ){
                var inputName = index;
                var inputError = value[0];

                this.showErrorMessage($("#airport_"+inputName), inputError);
            }.bind(this));

        }.bind(this)
    })
}

airport.hasRequiredField = function () {

    var message = "Please fill out this field.";
    var hasError = false;
    if (this.code.val() === "")
    {
        this.showErrorMessage(this.code, message);
        hasError = true;
    }

    if (this.name.val() === "")
    {
        this.showErrorMessage(this.name, message);
        hasError = true;
    }

    if (this.cityCode.val() === "")
    {
        this.showErrorMessage(this.cityCode, message);
        hasError = true;
    }

    if (this.cityName.val() === "")
    {
        this.showErrorMessage(this.cityName, message);
        hasError = true;
    }

    if (this.countryCode.val() === "")
    {
        this.showErrorMessage(this.countryCode, message);
        hasError = true;
    }

    return hasError;
}

airport.onCountryCodeSelected = function (event) {
    var name = $(event.target).select2('data')[0].text;
    var names = name.split('-');
    name = names[1];
    name = name.replace(/^\s+|\s+$/g, "");

    this.countryName.val(name);
}

airport.clearErrorMessage = function (item) {

    var hasError = item.parent().hasClass('has-danger');

    item.parent().removeClass('has-danger');
    item.parent().find('.form-control-feedback').remove();

}

airport.showErrorMessage = function (item, message) {
    item.parent().addClass('has-danger');

    var element = item.next().length > 0 ? item.next() : item;
    element.after('<span class="form-control-feedback"><small>' + message + '</small></span>');

    element.focus();

}

