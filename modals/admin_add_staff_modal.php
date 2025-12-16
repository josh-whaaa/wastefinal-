<!-- Add Staff Modal -->
        <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <form method="POST" action="../backend/admin_add_staff.php" id="addStaffForm">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Add New Staff</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label>First Name</label>
                      <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                      <label>Last Name</label>
                      <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                      <label>User Role</label>
                      <select name="user_role" class="form-select" required>
                        <option value="">Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Staff">Staff</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label>Gender</label>
                      <select name="gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label>Email</label>
                      <input type="email" name="email" id="staffEmail" class="form-control" required
                            pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"
                            title="Only Gmail addresses are allowed (e.g., example@gmail.com)">
                      <div id="emailFeedback" class="form-text text-danger"></div>
                    </div>
                    <div class="col-md-6">
                      <label>Contact</label>
                      <input type="text" name="contact" id="staffContact" class="form-control" required
                            maxlength="11" minlength="11"
                            pattern="^[0-9]{11}$"
                            title="Contact must be 11 digits only (e.g., 09123456789)">
                      <div id="contactFeedback" class="form-text text-danger"></div>
                    </div>
                    <select name="address" class="form-select" required>
                      <option value="">Select Barangay</option>
                      <?php while ($bgy = mysqli_fetch_assoc($barangayQuery)): ?>
                        <option value="<?= htmlspecialchars($bgy['barangay']); ?>">
                          <?= htmlspecialchars($bgy['barangay']); ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                    <div class="col-md-6">
                      <label>Birth Date</label>
                      <input type="date" name="birth_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                    <label for="editPassword">Password</label>
                    <div class="position-relative">
                      <input type="password" name="password" id="editPassword" class="form-control pe-5" required>
                      <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2 p-0 border-0 bg-transparent toggle-password" data-target="editPassword" tabindex="-1">
                        👁️‍🗨️
                      </button>
                    </div>
                  </div>
                  </div>
                </div>
                <div class="modal-footer mt-2">
                  <button type="submit" class="btn btn-primary">Save Staff</button>
                </div>
              </div>
            </form>
          </div>
        </div>
<?php
