<div class="modal fade" id="editModal<?= $row['brgy_id']; ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="../backend/admin_update_brgy.php">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Barangay</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="brgy_id" value="<?= $row['brgy_id'] ?>">
          <div class="mb-3">
            <label for="barangay" class="form-label">Barangay Name</label>
            <input type="text" class="form-control" name="barangay" value="<?= htmlspecialchars($row['barangay']) ?>" readonly>
          </div>
          <input type="hidden" name="latitude" value="<?= $row['latitude'] ?>">
          <input type="hidden" name="longitude" value="<?= $row['longitude'] ?>">
          <div class="mb-3">
            <label>Facebook Link</label>
            <input type="url" class="form-control" name="facebook_link" value="<?= $row['facebook_link'] ?>">
          </div>
          <div class="mb-3">
            <label>Link Text</label>
            <input type="text" class="form-control" name="link_text" value="<?= $row['link_text'] ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_brgy" class="btn btn-primary">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>
