<?php require_once('includes/init.php'); ?>
<?php cek_login($role = array(1, 2)); ?>

<?php
$errors = array();
$sukses = false;

$nomor_atlet = (isset($_POST['nomor_atlet'])) ? trim($_POST['nomor_atlet']) : '';
$nama_atlet = (isset($_POST['nama_atlet'])) ? trim($_POST['nama_atlet']) : '';
$kriteria = (isset($_POST['kriteria'])) ? $_POST['kriteria'] : array();


if(isset($_POST['submit'])):	
	
	// Validasi
	if(!$nomor_atlet) {
		$errors[] = 'Nomor atlet tidak boleh kosong';
	}	
	
	
	// Jika lolos validasi lakukan hal di bawah ini
	if(empty($errors)):
		
		$handle = $pdo->prepare('INSERT INTO atlet (nomor_atlet, nama_atlet, tanggal_input) VALUES (:nomor_atlet, :nama_atlet, :tanggal_input)');
		$handle->execute( array(
			'nomor_atlet' => $nomor_atlet,
			'nama_atlet' => $nama_atlet,
			'tanggal_input' => date('Y-m-d')
		) );
		$sukses = "atlet no. <strong>{$nomor_atlet}</strong> berhasil dimasukkan.";
		$id_atlet = $pdo->lastInsertId();
		
		// Jika ada kriteria yang diinputkan:
		if(!empty($kriteria)):
			foreach($kriteria as $id_kriteria => $nilai):
				$handle = $pdo->prepare('INSERT INTO nilai_atlet (id_atlet, id_kriteria, nilai) VALUES (:id_atlet, :id_kriteria, :nilai)');
				$handle->execute( array(
					'id_atlet' => $id_atlet,
					'id_kriteria' => $id_kriteria,
					'nilai' =>$nilai
				) );
			endforeach;
		endif;
		
		redirect_to('list-atlet.php?status=sukses-baru');		
		
	endif;

endif;
?>

<?php
$judul_page = 'Tambah atlet';
require_once('template-parts/header.php');
?>

	<div class="main-content-row">
	<div class="container clearfix">
	
		<?php include_once('template-parts/sidebar-atlet.php'); ?>
	
		<div class="main-content the-content">
			<h1>Tambah atlet</h1>
			
			<?php if(!empty($errors)): ?>
			
				<div class="msg-box warning-box">
					<p><strong>Error:</strong></p>
					<ul>
						<?php foreach($errors as $error): ?>
							<li><?php echo $error; ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				
			<?php endif; ?>			
			
			
				<form action="tambah-atlet.php" method="post">
					<div class="field-wrap clearfix">					
						<label>Nomor Atlet <span class="red">*</span></label>
						<input type="text" name="nomor_atlet" value="<?php echo $nomor_atlet; ?>">
					</div>					
					<div class="field-wrap clearfix">					
						<label>Nama Atlet</label>
						<input type="text" name="nama_atlet" value="<?php echo $nama_atlet; ?>">
					</div>			
					
					<h3>Nilai Kriteria</h3>
					<?php
					$query = $pdo->prepare('SELECT id_kriteria, nama, ada_pilihan FROM kriteria ORDER BY urutan_order ASC');			
					$query->execute();
					// menampilkan berupa nama field
					$query->setFetchMode(PDO::FETCH_ASSOC);
					
					if($query->rowCount() > 0):
					
						while($kriteria = $query->fetch()):							
						?>
						
							<div class="field-wrap clearfix">					
								<label><?php echo $kriteria['nama']; ?></label>
								<?php if(!$kriteria['ada_pilihan']): ?>
									<input type="number" step="0.001" name="kriteria[<?php echo $kriteria['id_kriteria']; ?>]">								
								<?php else: ?>
									
									<select name="kriteria[<?php echo $kriteria['id_kriteria']; ?>]">
										<option value="0">-- Pilih Variabel --</option>
										<?php
										$query3 = $pdo->prepare('SELECT * FROM pilihan_kriteria WHERE id_kriteria = :id_kriteria ORDER BY urutan_order ASC');			
										$query3->execute(array(
											'id_kriteria' => $kriteria['id_kriteria']
										));
										// menampilkan berupa nama field
										$query3->setFetchMode(PDO::FETCH_ASSOC);
										if($query3->rowCount() > 0): while($hasl = $query3->fetch()):
										?>
											<option value="<?php echo $hasl['nilai']; ?>"><?php echo $hasl['nama']; ?></option>
										<?php
										endwhile; endif;
										?>
									</select>
									
								<?php endif; ?>
							</div>	
						
						<?php
						endwhile;
						
					else:					
						echo '<p>Kriteria masih kosong.</p>';						
					endif;
					?>
					
					<div class="field-wrap clearfix">
						<button type="submit" name="submit" value="submit" class="button">Tambah atlet</button>
					</div>
				</form>
					
			
		</div>
	
	</div><!-- .container -->
	</div><!-- .main-content-row -->


<?php
require_once('template-parts/footer.php');