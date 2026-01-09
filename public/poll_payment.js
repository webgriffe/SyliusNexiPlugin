(function () {
  async function checkForCapturedPayment() {
    try {
      const response = await fetch(window.paymentStatusUrl)
      const data = await response.json()

      if (data.captured) {
        window.location.replace(window.afterUrl)
        return;
      }
    } catch (e) {
      console.log(e)
    }

    setTimeout(checkForCapturedPayment, 5000)
  }

  checkForCapturedPayment()
})();
