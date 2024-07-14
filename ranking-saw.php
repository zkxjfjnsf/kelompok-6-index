<?php
/* ---------------------------------------------
 * SPK SAW
 * Author: Hendra Bagus Setiawanto - 15111131
 * ------------------------------------------- */

/* ---------------------------------------------
 * Konek ke database & load fungsi-fungsi
 * ------------------------------------------- */
require_once('includes/init.php');

/* ---------------------------------------------
 * Load Header
 * ------------------------------------------- */
$judul_page = 'Perankingan Menggunakan Metode SAW';
require_once('template-parts/header.php');

/* ---------------------------------------------
 * Set jumlah digit di belakang koma
 * ------------------------------------------- */
$digit = 4;

/* ---------------------------------------------
 * Fetch semua kriteria
 * ------------------------------------------- */
$query = $pdo->prepare('SELECT id_kriteria, nama, type, bobot
	FROM kriteria ORDER BY urutan_order ASC');
$query->execute();
$query->setFetchMode(PDO::FETCH_ASSOC);
$kriterias = $query->fetchAll();

/* ---------------------------------------------
 * Fetch semua atlet (alternatif)
 * ------------------------------------------- */
$query2 = $pdo->prepare('SELECT id_atlet, nomor_atlet, nama_atlet FROM atlet');
$query2->execute();			
$query2->setFetchMode(PDO::FETCH_ASSOC);
$atlets = $query2->fetchAll();


/* >>> STEP 1 ===================================
 * Matrix Keputusan (X)
 * ------------------------------------------- */
$matriks_x = array();
$list_kriteria = array();
foreach($kriterias as $kriteria):
	$list_kriteria[$kriteria['id_kriteria']] = $kriteria;
	foreach($atlets as $atlet):
		
		$id_atlet = $atlet['id_atlet'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		// Fetch nilai dari db
		$query3 = $pdo->prepare('SELECT nilai FROM nilai_atlet
			WHERE id_atlet = :id_atlet AND id_kriteria = :id_kriteria');
		$query3->execute(array(
			'id_atlet' => $id_atlet,
			'id_kriteria' => $id_kriteria,
		));			
		$query3->setFetchMode(PDO::FETCH_ASSOC);
		if($nilai_atlet = $query3->fetch()) {
			// Jika ada nilai kriterianya
			$matriks_x[$id_kriteria][$id_atlet] = $nilai_atlet['nilai'];
		} else {			
			$matriks_x[$id_kriteria][$id_atlet] = 0;
		}

	endforeach;
endforeach;

/* >>> STEP 3 ===================================
 * Matriks Ternormalisasi (R)
 * ------------------------------------------- */
$matriks_r = array();
foreach($matriks_x as $id_kriteria => $nilai_atlets):
	
	$tipe = $list_kriteria[$id_kriteria]['type'];
	foreach($nilai_atlets as $id_alternatif => $nilai) {
		if($tipe == 'benefit') {
			$nilai_normal = $nilai / max($nilai_atlets);
		} elseif($tipe == 'cost') {
			$nilai_normal = min($nilai_atlets) / $nilai;
		}
		
		$matriks_r[$id_kriteria][$id_alternatif] = $nilai_normal;
	}
	
endforeach;


/* >>> STEP 4 ================================
 * Perangkingan
 * ------------------------------------------- */
$ranks = array();
foreach($atlets as $atlet):

	$total_nilai = 0;
	foreach($list_kriteria as $kriteria) {
	
		$bobot = $kriteria['bobot'];
		$id_atlet = $atlet['id_atlet'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		$nilai_r = $matriks_r[$id_kriteria][$id_atlet];
		$total_nilai = $total_nilai + ($bobot * $nilai_r);

	}
	
	$ranks[$atlet['id_atlet']]['id_atlet'] = $atlet['id_atlet'];
	$ranks[$atlet['id_atlet']]['nomor_atlet'] = $atlet['nomor_atlet'];
	$ranks[$atlet['id_atlet']]['nama_atlet'] = $atlet['nama_atlet'];
	$ranks[$atlet['id_atlet']]['nilai'] = $total_nilai;
	
endforeach;
 
?>

<div class="main-content-row">
<div class="container clearfix">	

	<div class="main-content main-content-full the-content">
		
		<h1><?php echo $judul_page; ?></h1>
		
		<!-- STEP 1. Matriks Keputusan(X) ==================== -->		
		<h3>Step 1: Matriks Keputusan (X)</h3>
		<table class="pure-table pure-table-striped">
			<thead>
				<tr class="super-top">
					<th rowspan="2" class="super-top-left">ID Kreator</th>
					<th rowspan="2" class="super-top-left">Nama Kreator</th>
					<th colspan="<?php echo count($kriterias); ?>">Kriteria</th>
				</tr>
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($atlets as $atlet): ?>
					<tr>
						<td><?php echo $atlet['nomor_atlet']; ?></td>
						<td><?php echo $atlet['nama_atlet']; ?></td>
						<?php						
						foreach($kriterias as $kriteria):
							$id_atlet = $atlet['id_atlet'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo $matriks_x[$id_kriteria][$id_atlet];
							echo '</td>';
						endforeach;
						?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- STEP 2. Bobot Preferensi (W) ==================== -->
		<h3>Step 2: Bobot Preferensi (W)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>
				<tr>
					<th>Nama Kriteria</th>
					<th>Type</th>
					<th>Bobot (W)</th>						
				</tr>
			</thead>
			<tbody>
				<?php foreach($kriterias as $hasil): ?>
					<tr>
						<td><?php echo $hasil['nama']; ?></td>
						<td>
						<?php
						if($hasil['type'] == 'benefit') {
							echo 'Benefit';
						} elseif($hasil['type'] == 'cost') {
							echo 'Cost';
						}							
						?>
						</td>
						<td><?php echo $hasil['bobot']; ?></td>							
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- Step 3: Matriks Ternormalisasi (R) ==================== -->
		<h3>Step 3: Matriks Ternormalisasi (R)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>
				<tr class="super-top">
					<th rowspan="2" class="super-top-left">ID Kreator</th>
					<th rowspan="2" class="super-top-left">Nama Kreator</th>
					<th colspan="<?php echo count($kriterias); ?>">Kriteria</th>
				</tr>
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($atlets as $atlet): ?>
					<tr>
						<td><?php echo $atlet['nomor_atlet']; ?></td>
						<td><?php echo $atlet['nama_atlet']; ?></td>
						<?php						
						foreach($kriterias as $kriteria):
							$id_atlet = $atlet['id_atlet'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo round($matriks_r[$id_kriteria][$id_atlet], $digit);
							echo '</td>';
						endforeach;
						?>
					</tr>
				<?php endforeach; ?>				
			</tbody>
		</table>		
		
		
		<!-- Step 4: Perangkingan ==================== -->
		<?php
		$no=1;		
		$sorted_ranks = $ranks;		
		// Sorting
		if(function_exists('array_multisort')):
			$nomor_atlet = array();
			$nilai = array();
			foreach ($sorted_ranks as $key => $row) {
				$nomor_atlet[$key]  = $row['nomor_atlet'];
				$nilai[$key] = $row['nilai'];
			}
			array_multisort($nilai, SORT_DESC, $nomor_atlet, SORT_ASC, $sorted_ranks);
		endif;
		?>		
		<h3>Step 4: Perangkingan (V)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>					
				<tr>
					<th class="super-top-left">ID Kreator</th>
					<th class="super-top-left">Nama Kreator</th>
					<th class="super-top-left">Ranking</th>
					<th class="super-top-left">Peringkat</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($sorted_ranks as $atlet ): ?>
					<tr>
						<td><?php echo $atlet['nomor_atlet']; ?></td>
						<td><?php echo $atlet['nama_atlet']; ?></td>
						<td><?php echo round($atlet['nilai'], $digit); ?></td>
						<td><?php echo $no++; ?></td>											
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>			
		
	</div>

</div><!-- .container -->
</div><!-- .main-content-row -->

<?php
require_once('template-parts/footer.php');