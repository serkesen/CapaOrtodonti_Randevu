const input = document.querySelector("#ContactMobile");
const iti = window.intlTelInput(input, {
    loadUtilsOnInit: "../intl-tel-input/utils.js",
    initialCountry: "tr",
    separateDialCode: true,
});
