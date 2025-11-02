<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <p class="text-muted mb-0" style="font-size:14px;">Select a branch to manage payroll</p>
  </div>
  <a href="../hr/index.php" class="btn btn-outline-secondary btn-sm">← Back to HR Dashboard</a>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <h5 class="fw-semibold mb-3 text-primary">🏢 Select a Branch</h5>
    <div class="list-group">
      <?php
      $branches = [
        ['name' => 'Main Branch', 'location' => 'Makati City'],
        ['name' => 'Ayala Center Cebu', 'location' => 'Cebu Business Park, Cebu City'],
        ['name' => 'Davao Gateway', 'location' => 'Ecoland, Davao City'],
        ['name' => 'SM Megamall Branch', 'location' => 'Mandaluyong, Metro Manila'],
      ];
      foreach($branches as $b): ?>
      <div class="list-group-item d-flex justify-content-between align-items-center">
        <span><?= $b['name'] ?> <small class="text-muted">(<?= $b['location'] ?>)</small></span>
        <button class="btn btn-outline-primary btn-sm load-page"
                data-page="payroll_departments.php"
                data-branch="<?= $b['name'] ?>">
          View Departments
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
