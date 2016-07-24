<div class="wrap">
  <?php $plugin_data = get_plugin_data(__DIR__.'/../CultureObjectDisplay.php'); ?>

  <h2><?php _e('Culture Object Display Settings','culture-object-display'); ?> <small><?php printf( /* Translators: 1 Version Number */ __('Version %s','culture-object-display'), $plugin_data['Version']); ?> by <a href="http://www.thirty8.co.uk">Thirty8 Digital</a>.</small></h2>

  <form method="POST" action="options.php">
    <?php 
      settings_fields('cos_display_settings');
      do_settings_sections('cos_display_settings');
      submit_button();
    ?>
  </form>
</div>