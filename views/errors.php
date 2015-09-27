<?php $messages = Lockdown_Manager::instance()->admin->get_messages(); ?>
<?php if ( ! empty( $messages ) ) : ?>
	<?php foreach ( $messages as $message ) : ?>
		<div class="<?php if ( 'error' === $message['type'] ) { echo 'error'; } else { echo 'updated'; } ?>">
			<p><?php echo esc_html( $message['message'] ); ?></p>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
