<?php
// Add or edit an article. An article goes Draft → Ready for review → Published; only published articles are on the
// site, in the sitemap and in Google. Anything written with AI help should be checked by a person before publishing.
require __DIR__ . '/../includes/admin.php';
$admin = require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $id ? ($stmt->fetch() ?: null) : null;
if ($id && !$post) {
    flash('That article no longer exists.', 'error');
    redirect('seo.php?t=guides');
}
$form = $post ?? ['title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'image' => null, 'category_id' => null, 'product_ids' => '', 'seo_title' => '', 'meta_description' => '', 'focus_keyword' => '', 'secondary_keywords' => '', 'author' => $admin['name'] ?? '', 'status' => 'draft', 'published_at' => null];
$errors = [];

if (is_post()) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete' && $post) {
        db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        flash('Article deleted.');
        redirect('seo.php?t=guides');
    }
    $status = in_array($_POST['status'] ?? '', ['draft', 'review', 'published'], true) ? $_POST['status'] : 'draft';
    $form = array_merge($form, [
        'title'            => post_str('title'),
        'excerpt'          => post_str('excerpt'),
        'body'             => post_str('body'),
        'category_id'      => post_int('category_id'),
        'product_ids'      => implode(',', array_slice(array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,]+/', (string) post_str('product_ids')))))), 0, 12)) ?: null,
        'seo_title'        => post_str('seo_title'),
        'meta_description' => post_str('meta_description'),
        'focus_keyword'    => post_str('focus_keyword'),
        'secondary_keywords' => post_str('secondary_keywords'),
        'author'           => post_str('author'),
        'status'           => $status,
    ]);
    $form['slug'] = slugify((string) (post_str('slug') ?: $form['title']));
    $when = post_str('published_at');
    $form['published_at'] = $when && strtotime($when) ? date('Y-m-d H:i:s', strtotime($when)) : ($status === 'published' ? ($post['published_at'] ?? date('Y-m-d H:i:s')) : ($post['published_at'] ?? null));
    if (!$form['title']) {
        $errors['title'] = 'Give the article a title.';
    }
    if ($status === 'published' && mb_strlen(seo_plain(seo_markdown((string) $form['body']))) < 300) {
        $errors['body'] = 'A published article needs at least a few paragraphs (300+ characters). Save it as a draft until it is ready.';
    }
    $dupe = db()->prepare('SELECT COUNT(*) FROM posts WHERE slug = ? AND id <> ?');
    $dupe->execute([$form['slug'], $id]);
    if ((int) $dupe->fetchColumn() > 0) {
        $form['slug'] .= '-' . date('ymd');
    }
    try {
        if (!$errors && ($img = upload_image('image'))) {
            $form['image'] = $img['path'];
        } elseif (!$errors && !empty($_POST['image_remove'])) {
            $form['image'] = null;
        }
    } catch (RuntimeException $ex) {
        $errors['image'] = $ex->getMessage();
    }
    if (!$errors) {
        $cols = ['title', 'slug', 'excerpt', 'body', 'image', 'category_id', 'product_ids', 'seo_title', 'meta_description', 'focus_keyword', 'secondary_keywords', 'author', 'status', 'published_at'];
        $values = array_map(fn ($c) => $form[$c] ?? null, $cols);
        if ($post) {
            db()->prepare('UPDATE posts SET ' . implode(', ', array_map(fn ($c) => "$c = ?", $cols)) . ' WHERE id = ?')->execute([...$values, $id]);
        } else {
            db()->prepare('INSERT INTO posts (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')->execute($values);
            $id = (int) db()->lastInsertId();
        }
        flash(['draft' => 'Article saved as a draft.', 'review' => 'Article saved and marked ready for review.', 'published' => 'Article published.'][$status]);
        redirect('post-edit.php?id=' . $id);
    }
}

$cats = [];
foreach (category_tree(true) as $main) {
    $cats[] = $main;
    foreach ($main['children'] as $sub) {
        $cats[] = $sub;
    }
}
admin_header($post ? 'Edit article' : 'New article', 'seo');
$link = $post ? '../' . post_url($post) : null;
?>
<a href="seo.php?t=guides" class="small text-decoration-none">← All articles</a>
<?php if ($errors): ?><div class="alert alert-danger mt-3">Please fix the highlighted fields.</div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="mt-3">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card mb-4">
        <div class="card-header">Article</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="title">Title *</label>
            <input class="form-control<?= isset($errors['title']) ? ' is-invalid' : '' ?>" id="title" name="title" maxlength="200" value="<?= e($form['title']) ?>" placeholder="How much RAM do you need in a laptop?" required>
            <?php if (isset($errors['title'])): ?><div class="invalid-feedback d-block"><?= e($errors['title']) ?></div><?php endif; ?>
            <div class="form-text">Use a clear title that matches the reader’s question. Answer it directly in the article.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="excerpt">Summary</label>
            <textarea class="form-control" id="excerpt" name="excerpt" rows="2" maxlength="320" placeholder="One or two sentences answering the question straight away."><?= e($form['excerpt']) ?></textarea>
          </div>
          <label class="form-label" for="body">Text</label>
          <textarea class="form-control<?= isset($errors['body']) ? ' is-invalid' : '' ?>" id="body" name="body" rows="18" spellcheck="true" placeholder="## Section heading&#10;A paragraph.&#10;&#10;- A point&#10;- Another point&#10;&#10;See our [business laptops](business-laptops)."><?= e($form['body']) ?></textarea>
          <?php if (isset($errors['body'])): ?><div class="invalid-feedback d-block"><?= e($errors['body']) ?></div><?php endif; ?>
          <div class="form-text">Formatting: <code>## Heading</code>, <code>### Subheading</code>, <code>- point</code>, <code>1. step</code>, <code>**bold**</code>, <code>[link text](laptops)</code>. Blank line = new paragraph. Use real facts and link to the products you mention.</div>
        </div>
      </div>
      <div class="card mb-4">
        <div class="card-header">Search engines</div>
        <div class="card-body">
          <div class="mb-3"><label class="form-label" for="seo_title">Search title</label><input class="form-control" id="seo_title" name="seo_title" maxlength="120" value="<?= e($form['seo_title']) ?>" placeholder="<?= e(($form['title'] ?: 'Article title') . ' | ' . seo_store()) ?>" data-count="60"></div>
          <div class="mb-3"><label class="form-label" for="meta_description">Search description</label><textarea class="form-control" id="meta_description" name="meta_description" rows="2" maxlength="320" placeholder="The summary is used when this is empty." data-count="158"><?= e($form['meta_description']) ?></textarea></div>
          <div class="mb-3"><label class="form-label" for="focus_keyword">Focus keyword</label><input class="form-control" id="focus_keyword" name="focus_keyword" maxlength="120" value="<?= e($form['focus_keyword']) ?>" placeholder="e.g. how to choose a laptop in Kenya"></div>
          <div class="mb-3"><label class="form-label" for="secondary_keywords">Related search phrases</label><textarea class="form-control" id="secondary_keywords" name="secondary_keywords" rows="2" maxlength="600" placeholder="Separate phrases with commas"><?= e($form['secondary_keywords']) ?></textarea><div class="form-text">Editorial reminders only—not published as meta-keywords. Use naturally where genuinely helpful; Google does not use the meta-keywords tag for rankings.</div></div>
          <div><label class="form-label" for="slug">Address</label><div class="input-group"><span class="input-group-text small"><?= e(preg_replace('#^https?://#', '', seo_site_url())) ?>/</span><input class="form-control" id="slug" name="slug" maxlength="150" value="<?= e($form['slug']) ?>" placeholder="made from the title"><span class="input-group-text small">-g<?= $post ? (int) $post['id'] : '…' ?></span></div><div class="form-text">Changing it later is safe: the old address moves to the new one.</div></div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card mb-4">
        <div class="card-header">Publishing</div>
        <div class="card-body">
          <label class="form-label" for="status">Status</label>
          <select class="form-select mb-2" id="status" name="status">
            <?php foreach (['draft' => 'Draft', 'review' => 'Ready for review', 'published' => 'Published'] as $k => $label): ?><option value="<?= $k ?>" <?= $form['status'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
          </select>
          <p class="section-note">Before publishing, check every fact (prices, specs, warranty) and that it really helps a shopper decide.</p>
          <label class="form-label" for="published_at">Publish date</label>
          <input class="form-control mb-3" type="datetime-local" id="published_at" name="published_at" value="<?= $form['published_at'] ? e(date('Y-m-d\TH:i', strtotime((string) $form['published_at']))) : '' ?>">
          <label class="form-label" for="author">Author</label>
          <input class="form-control" id="author" name="author" maxlength="80" value="<?= e($form['author']) ?>" placeholder="Your name or the shop’s">
          <?php if ($link): ?><a class="btn btn-outline-secondary btn-sm mt-3" href="<?= e($link) ?>" target="_blank" rel="noopener"><?= $form['status'] === 'published' ? 'View ↗' : 'Preview ↗' ?></a><?php endif; ?>
        </div>
      </div>
      <div class="card mb-4">
        <div class="card-header">Links to products</div>
        <div class="card-body">
          <label class="form-label" for="category_id">Category</label>
          <select class="form-select mb-2" id="category_id" name="category_id">
            <option value="">None</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $form['category_id'] === $c['id'] ? 'selected' : '' ?>><?= $c['parent_id'] ? '— ' : '' ?><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
          <p class="section-note">The article is linked from that category page and its products.</p>
          <label class="form-label" for="product_ids">Products to show (numbers)</label>
          <input class="form-control" id="product_ids" name="product_ids" value="<?= e($form['product_ids']) ?>" placeholder="e.g. 369, 370">
          <div class="form-text">The product number is in the edit page address (product-edit.php?id=<strong>369</strong>). Empty = popular products from the category.</div>
        </div>
      </div>
      <div class="card mb-4">
        <div class="card-header">Picture</div>
        <div class="card-body">
          <?php if ($form['image']): ?><img src="<?= e(admin_asset($form['image'])) ?>" alt="" class="img-fluid rounded mb-2"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="image_remove" name="image_remove" value="1"><label class="form-check-label small" for="image_remove">Remove picture</label></div><?php endif; ?>
          <input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp">
          <?php if (isset($errors['image'])): ?><div class="invalid-feedback d-block"><?= e($errors['image']) ?></div><?php endif; ?>
          <div class="form-text">Wide picture, 1200 × 675 px or larger. Shown at the top and when the article is shared.</div>
        </div>
      </div>
    </div>
  </div>
  <div class="sticky-actions d-flex gap-2 align-items-center">
    <button class="btn btn-primary px-4" type="submit" name="action" value="save"><?= $post ? 'Save changes' : 'Create article' ?></button>
    <a class="btn btn-outline-secondary" href="seo.php?t=guides">Cancel</a>
    <?php if ($post): ?><button class="btn btn-outline-danger ms-auto" type="submit" name="action" value="delete" formnovalidate data-confirm="Delete this article?">Delete</button><?php endif; ?>
  </div>
</form>
<?php admin_footer(); ?>
