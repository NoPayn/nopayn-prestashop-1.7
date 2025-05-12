$(document).ready(function () {
    $("#payment-confirmation").find(".btn").click(function () {
        const selectedOption = $('input[name="payment-option"]:checked');

        const container = selectedOption.closest('.payment-option');

        if (container.find('img[src*="nopaynapplepay.svg"]').length > 0) {
            if (!window.ApplePaySession) {
                alert(applepay_isnt_available);
                return false;
            }
        }

        return true;
    });
});