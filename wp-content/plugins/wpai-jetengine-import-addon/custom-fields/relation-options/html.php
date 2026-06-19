<div class="input">
    <input type="hidden" name="<?php echo esc_attr($html_name); ?>[keep_existing_relations]" value="0" />
    <label>
        <input type="checkbox" value="1" name="<?php echo esc_attr($html_name); ?>[keep_existing_relations]" <?php echo (!empty($field_value['keep_existing_relations'])) ? 'checked="checked"' : ''; ?>>
        Keep existing relations and only append new ones.
    </label>
</div>