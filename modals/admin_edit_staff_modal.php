<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="update_staff.php" id="editStaffForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="admin_id" id="editAdminId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editFirstName" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editLastName" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="editLastName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editUserRole" class="form-label">User Role</label>
                            <select name="user_role" id="editUserRole" class="form-select" required>
                                <option value="Admin">Admin</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control" required
                                pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"
                                title="Only Gmail addresses are allowed (e.g., example@gmail.com)">
                            <div id="editEmailFeedback" class="form-text text-danger"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="editContact" class="form-label">Contact</label>
                            <input type="text" name="contact" id="editContact" class="form-control"
                                maxlength="11" minlength="11" required pattern="^[0-9]{11}$"
                                title="Contact must be 11 digits only (e.g., 09123456789)">
                            <div id="editContactFeedback" class="form-text text-danger"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="editAddress" class="form-label">Barangay</label>
                            <select name="address" id="editAddress" class="form-select" required>
                                 <option value="">Select Barangay</option>
                                <?php foreach ($barangays as $bgy): ?>
                                    <option value="<?= htmlspecialchars($bgy); ?>">
                                        <?= htmlspecialchars($bgy); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editPassword" class="form-label">Password</label>
                            <div class="position-relative">
                                <input type="password" name="password" id="editPassword" class="form-control pe-5">
                                <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2 p-0 border-0 bg-transparent toggle-password" data-target="editPassword" tabindex="-1">
                                    👁️‍🗨️
                                </button>
                            </div>
                            <div class="form-text">Leave blank if you don’t want to change password</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer mt-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Staff</button>
                </div>
            </div>
        </form>
    </div>
</div>