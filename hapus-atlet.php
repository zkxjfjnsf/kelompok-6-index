<?php require_once('includes/init.php'); ?>
<?php cek_login($role = array(1, 2)); ?>

<?php
$ada_error = false;
$result = '';

$id_atlet = (isset($_GET['id'])) ? trim($_GET['id']) : '';

if(!$id_atlet) {
	$ada_error = 'Maaf, data tidak dapat diproses.';
} else {
	$query = $pdo->prepare('SELECT id_atlet FROM atlet WHERE id_atlet = :id_atlet');
	$query->execute(array('id_atlet' => $id_atlet));
	$result = $query->fetch();
	
	if(empty($result)) {
		$ada_error = 'Maaf, data tidak dapat diproses.';
	} else {
		
		$handle = $pdo->prepare('DELETE FROM nilai_atlet WHERE id_atlet = :id_atlet');				
		$handle->execute(array(
			'id_atlet' => $result['id_atlet']
		));
		$handle = $pdo->prepare('DELETE FROM atlet WHERE id_atlet = :id_atlet');				
		$handle->execute(array(
			'id_atlet' => $result['id_atlet']
		));
		redirect_to('list-atlet.php?status=sukses-hapus');
		
	}
}
?>

<?php
$judul_page = 'Hapus atlet';
require_once('template-parts/header.php');
?>

	<div class="main-content-row">
	<div class="container clearfix">
	
		<?php include_once('template-parts/sidebar-atlet.php'); ?>
	
		<div class="main-content the-content">
			<h1><?php echo $judul_page; ?></h1>
			
			<?php if($ada_error): ?>
			
				<?php echo '<p>'.$ada_error.'</p>'; ?>	
			
			<?php endif; ?>
			
		</div>
	
	</div><!-- .container -->
	</div><!-- .main-content-row -->


<?php
require_once('template-parts/footer.php');