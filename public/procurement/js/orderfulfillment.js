
    function toNumber(v) {
      const n = parseFloat(v);
      return isFinite(n) ? n : 0;
    }

    function recalculate() {
      const qty = toNumber(document.getElementById('quantity').value);
      const price = toNumber(document.getElementById('price_per_item').value);
      const total = qty * price;
      document.getElementById('total_amount').value = total.toFixed(2);
      document.getElementById('total_hidden').value = total.toFixed(2);
    }

    function syncTotal() {
      recalculate();
      return true;
    }

    window.addEventListener('DOMContentLoaded', recalculate);
