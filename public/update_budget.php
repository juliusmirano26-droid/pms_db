<!-- Add this button near the "Add Expense" button -->
<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#budgetModal">
    Edit Budget
</button>

<!-- Add this Modal -->
<div class="modal fade" id="budgetModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="update_budget.php" method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Update Budget</h5></div>
            <div class="modal-body">
                <input type="number" name="new_budget" class="form-control" step="0.01" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Budget</button>
            </div>
        </form>
    </div>
</div>