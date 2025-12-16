require('../../config.php');
require_login();

$block = new block_tareaspendientes();
echo $block->render_tasks();
