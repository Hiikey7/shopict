<?php if (current_admin()): ?>
    </main>
  </div>
</div>
<?php else: ?>
</div>
<?php endif; ?>
<script src="<?= e(app_url('assets/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(app_url(asset_v('admin/admin.js'))) ?>"></script>
</body>
</html>
