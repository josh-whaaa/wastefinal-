<?php
function renderEditClientModal($row) {
    return '
    <div class="modal fade" id="editClientModal' . $row['client_id'] . '" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="../backend/admin_edit_client.php" class="edit-client-form">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Edit Client: ' . htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . '</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="client_id" value="' . $row['client_id'] . '">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">First Name</label>
                  <input type="text" class="form-control" name="first_name" value="' . htmlspecialchars($row['first_name']) . '" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Last Name</label>
                  <input type="text" class="form-control" name="last_name" value="' . htmlspecialchars($row['last_name']) . '" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" name="email" value="' . htmlspecialchars($row['email']) . '" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Contact</label>
                  <input type="text" class="form-control" name="contact" value="' . htmlspecialchars($row['contact']) . '" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Barangay</label>
                  <input type="text" class="form-control" name="barangay" value="' . htmlspecialchars($row['barangay']) . '" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Password (leave blank to keep current)</label>
                  <input type="password" class="form-control" name="password">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </div>
        </form>
      </div>
    </div>';
}
?>
