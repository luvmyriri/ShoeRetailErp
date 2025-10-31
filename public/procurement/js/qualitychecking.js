
  // Calculate total amount based on quantity and price
  function recalculate() {
    const qty = parseFloat(document.getElementById('quantity').value) || 0;
    const price = parseFloat(document.getElementById('price_per_item').value) || 0;
    const total = qty * price;
    document.getElementById('total_amount').value = total.toFixed(2);
    document.getElementById('total_hidden').value = total;
  }

  // Ensure Quantity Passed does not exceed Quantity Received
  function updatePassedLimit() {
    const received = parseInt(document.getElementById('quantity_received').value) || 0;
    let passed = parseInt(document.getElementById('quantity_passed').value) || 0;

    if (passed > received) {
      passed = received;
      document.getElementById('quantity_passed').value = passed;
    }

    const failed = received - passed;
    document.getElementById('quantity_failed').value = failed;
  }

  // Preview uploaded image
  function previewImage(event) {
    const preview = document.getElementById('image_preview');
    preview.innerHTML = ''; // Clear previous content

    const file = event.target.files[0];
    if(file) {
      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      preview.appendChild(img);
    } else {
      preview.innerHTML = '<span>Click or drag image here</span>';
    }
  }

  // Update character counter for description
  function updateCharCount() {
    const textarea = document.getElementById('description');
    const counter = document.getElementById('char_counter');
    counter.textContent = `${textarea.value.length} / 10000`;
  }

  // Sync all calculations before submit
  function syncTotal() {
    recalculate();
    updatePassedLimit(); // calculates failed automatically
  }

  // Initialize counters on page load
  document.addEventListener("DOMContentLoaded", function() {
    updateCharCount();
    updatePassedLimit();
    recalculate();
  });

