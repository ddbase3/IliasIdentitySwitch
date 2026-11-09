<?php
$labels = (array)($this->_['phrases'] ?? []);
$get = static fn(string $key): string => (string)($labels[$key] ?? '');
$esc = static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$active = ($this->_['active'] ?? false) === true;
?>
<link rel="stylesheet" href="<?php echo $esc((string)$this->_['widgetCss']); ?>">
<link rel="stylesheet" href="<?php echo $esc((string)$this->_['pageCss']); ?>">
<section class="identity-switch-menu" data-identity-switch-menu>
	<p class="identity-switch-description"><?php echo $esc($get($active ? 'restore_description' : 'choose_description')); ?></p>
	<p role="alert" aria-live="assertive" data-identity-switch-error hidden tabindex="-1"></p>
	<form data-identity-switch-form method="post" action="<?php echo $esc((string)$this->_['commandUrl']); ?>">
		<input type="hidden" name="csrf" value="<?php echo $esc((string)$this->_['token']); ?>">
		<input type="hidden" name="operation" value="<?php echo $active ? 'finish' : 'begin'; ?>">
		<?php if ($active): ?>
			<p><?php echo $esc($get('restore_notice')); ?></p>
		<?php else: ?>
			<div data-identity-switch-select></div>
			<input type="hidden" name="identity" value="" data-identity-switch-value>
		<?php endif; ?>
		<div class="identity-switch-actions">
			<button type="submit" class="btn btn-primary"><?php echo $esc($get($active ? 'restore_button' : 'switch_button')); ?></button>
		</div>
	</form>
	<script type="application/json" data-identity-switch-config><?php
		echo json_encode([
			'active' => $active,
			'searchUrl' => (string)$this->_['searchUrl'],
			'widgetJs' => (string)$this->_['widgetJs'],
			'labels' => [
				'search_placeholder' => $get('search_placeholder'),
				'search_label' => $get('search_label'),
				'search_error' => $get('search_error'),
				'action_error' => $get('action_error'),
				'no_selection' => $get('no_selection'),
				'options' => $get('options'),
				'loading' => $get('loading'),
				'no_results' => $get('no_results'),
				'load_more' => $get('load_more'),
				'required' => $get('required'),
				'show_options' => $get('show_options'),
				'hide_options' => $get('hide_options'),
				'remove_item' => $get('remove_item'),
				'removed_item' => $get('removed_item'),
				'backspace_hint' => $get('backspace_hint'),
				'min_chars' => $get('min_chars'),
				'results_count' => $get('results_count')
			]
		], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
	?></script>
	<script type="module" src="<?php echo $esc((string)$this->_['pageJs']); ?>"></script>
</section>
