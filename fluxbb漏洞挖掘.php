else if ($action == 'upload_avatar' || $action == 'upload_avatar2')
{
	if ($pun_config['o_avatars'] == '0')
		message($lang_profile['Avatars disabled']);

	if ($pun_user['id'] != $id && !$pun_user['is_admmod'])
		message($lang_common['No permission'], false, '403 Forbidden');

	if (isset($_POST['form_sent']))
	{
		if (!isset($_FILES['req_file']))
			message($lang_profile['No file']);

		// Make sure they got here from the site
		confirm_referrer('profile.php');

		$uploaded_file = $_FILES['req_file'];

		// Make sure the upload went smooth
		if (isset($uploaded_file['error']))
		{
			switch ($uploaded_file['error'])
			{
				case 1: // UPLOAD_ERR_INI_SIZE
				case 2: // UPLOAD_ERR_FORM_SIZE
					message($lang_profile['Too large ini']);
					break;

				case 3: // UPLOAD_ERR_PARTIAL
					message($lang_profile['Partial upload']);
					break;

				case 4: // UPLOAD_ERR_NO_FILE
					message($lang_profile['No file']);
					break;

				case 6: // UPLOAD_ERR_NO_TMP_DIR
					message($lang_profile['No tmp directory']);
					break;

				default:
					// No error occured, but was something actually uploaded?
					if ($uploaded_file['size'] == 0)
						message($lang_profile['No file']);
					break;
			}
		}

		if (is_uploaded_file($uploaded_file['tmp_name']))
		{
			// Preliminary file check, adequate in most cases
			$allowed_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
			if (!in_array($uploaded_file['type'], $allowed_types))
				message($lang_profile['Bad type']);

			// Make sure the file isn't too big
			if ($uploaded_file['size'] > $pun_config['o_avatars_size'])
				message(sprintf($lang_profile['Too large'], forum_number_format($pun_config['o_avatars_size'])));

			// Move the file to the avatar directory. We do this before checking the width/height to circumvent open_basedir restrictions
			if (!@move_uploaded_file($uploaded_file['tmp_name'], PUN_ROOT.$pun_config['o_avatars_dir'].'/'.$id.'.tmp'))
				message($lang_profile['Move failed'].' <a href="mailto:'.pun_htmlspecialchars($pun_config['o_admin_email']).'">'.pun_htmlspecialchars($pun_config['o_admin_email']).'</a>.');

			list($width, $height, $type,) = @getimagesize(PUN_ROOT.$pun_config['o_avatars_dir'].'/'.$id.'.tmp');

			// Determine type
			if ($type == IMAGETYPE_GIF)
				$extension = '.gif';
			else if ($type == IMAGETYPE_JPEG)
				$extension = '.jpg';
			else if ($type == IMAGETYPE_PNG)
				$extension = '.png';
			else
			{
				// Invalid type
				@unlink(PUN_ROOT.$pun_config['o_avatars_dir'].'/'.$id.'.tmp');
				message($lang_profile['Bad type']);
			}

			// Now check the width/height
			if (empty($width) || empty($height) || $width > $pun_config['o_avatars_width'] || $height > $pun_config['o_avatars_height'])
			{
				@unlink(PUN_ROOT.$pun_config['o_avatars_dir'].'/'.$id.'.tmp');
				message(sprintf($lang_profile['Too wide or high'], $pun_config['o_avatars_width'], $pun_config['o_avatars_height']));
			}

			// Delete any old avatars and put the new one in place
			delete_avatar($id);
			@rename(PUN_ROOT.$pun_config['o_avatars_dir'].'/'.$id.'.tmp', PUN_ROOT.$pun_config['o_avatars_dir'].'/'.$id.$extension);
			@chmod(PUN_ROOT.$pun_config['o_avatars_dir'].'/'.$id.$extension, 0644);
		}
		else
			message($lang_profile['Unknown failure']);

		redirect('profile.php?section=personality&amp;id='.$id, $lang_profile['Avatar upload redirect']);
	}

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Profile'], $lang_profile['Upload avatar']);
	$required_fields = array('req_file' => $lang_profile['File']);
	$focus_element = array('upload_avatar', 'req_file');
	define('PUN_ACTIVE_PAGE', 'profile');
	require PUN_ROOT.'header.php';
