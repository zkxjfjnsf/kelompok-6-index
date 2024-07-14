<?php
/* ---------------------------------------------
 * SPK TOPSIS
 * Author: Hendra Bagus Setiawanto - 15111131
 * ------------------------------------------- */

/* ---------------------------------------------
 * Konek ke database & load fungsi-fungsi
 * ------------------------------------------- */
require_once('includes/init.php');

/* ---------------------------------------------
 * Load Header
 * ------------------------------------------- */
$judul_page = 'Perankingan Menggunakan Metode TOPSIS';
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
foreach($kriterias as $kriteria):
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
	
	// Mencari akar dari penjumlahan kuadrat
	$jumlah_kuadrat = 0;
	foreach($nilai_atlets as $nilai_atlet):
		$jumlah_kuadrat += pow($nilai_atlet, 2);
	endforeach;
	$akar_kuadrat = sqrt($jumlah_kuadrat);
	
	// Mencari hasil bagi akar kuadrat
	// Lalu dimasukkan ke array $matriks_r
	foreach($nilai_atlets as $id_atlet => $nilai_atlet):
		$matriks_r[$id_kriteria][$id_atlet] = $nilai_atlet / $akar_kuadrat;
	endforeach;
	
endforeach;


/* >>> STEP 4 ===================================
 * Matriks Y
 * ------------------------------------------- */
$matriks_y = array();
foreach($kriterias as $kriteria):
	foreach($atlets as $atlet):
		
		$bobot = $kriteria['bobot'];
		$id_atlet = $atlet['id_atlet'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		$nilai_r = $matriks_r[$id_kriteria][$id_atlet];
		$matriks_y[$id_kriteria][$id_atlet] = $bobot * $nilai_r;

	endforeach;
endforeach;


/* >>> STEP 5 ================================
 * Solusi Ideal Positif & Negarif
 * ------------------------------------------- */
$solusi_ideal_positif = array();
$solusi_ideal_negatif = array();
foreach($kriterias as $kriteria):

	$id_kriteria = $kriteria['id_kriteria'];
	$type_kriteria = $kriteria['type'];
	
	$nilai_max = max($matriks_y[$id_kriteria]);
	$nilai_min = min($matriks_y[$id_kriteria]);
	
	if($type_kriteria == 'benefit'):
		$s_i_p = $nilai_max;
		$s_i_n = $nilai_min;
	elseif($type_kriteria == 'cost'):
		$s_i_p = $nilai_min;
		$s_i_n = $nilai_max;
	endif;
	
	$solusi_ideal_positif[$id_kriteria] = $s_i_p;
	$solusi_ideal_negatif[$id_kriteria] = $s_i_n;

endforeach;


/* >>> STEP 6 ================================
 * Jarak Ideal Positif & Negatif
 * ------------------------------------------- */
$jarak_ideal_positif = array();
$jarak_ideal_negatif = array();
foreach($atlets as $atlet):

	$id_atlet = $atlet['id_atlet'];		
	$jumlah_kuadrat_jip = 0;
	$jumlah_kuadrat_jin = 0;
	
	// Mencari penjumlahan kuadrat
	foreach($matriks_y as $id_kriteria => $nilai_atlets):
		
		$hsl_pengurangan_jip = $nilai_atlets[$id_atlet] - $solusi_ideal_positif[$id_kriteria];
		$hsl_pengurangan_jin = $nilai_atlets[$id_atlet] - $solusi_ideal_negatif[$id_kriteria];
		
		$jumlah_kuadrat_jip += pow($hsl_pengurangan_jip, 2);
		$jumlah_kuadrat_jin += pow($hsl_pengurangan_jin, 2);
	
	endforeach;
	
	// Mengakarkan hasil penjumlahan kuadrat
	$akar_kuadrat_jip = sqrt($jumlah_kuadrat_jip);
	$akar_kuadrat_jin = sqrt($jumlah_kuadrat_jin);
	
	// Memasukkan ke array matriks jip & jin
	$jarak_ideal_positif[$id_atlet] = $akar_kuadrat_jip;
	$jarak_ideal_negatif[$id_atlet] = $akar_kuadrat_jin;
	
endforeach;


/* >>> STEP 7 ================================
 * Perangkingan
 * ------------------------------------------- */
$ranks = array();
foreach($atlets as $atlet):

	$s_negatif = $jarak_ideal_negatif[$atlet['id_atlet']];
	$s_positif = $jarak_ideal_positif[$atlet['id_atlet']];	
	
	$nilai_v = $s_negatif / ($s_positif + $s_negatif);
	
	$ranks[$atlet['id_atlet']]['id_atlet'] = $atlet['id_atlet'];
	$ranks[$atlet['id_atlet']]['nomor_atlet'] = $atlet['nomor_atlet'];
	$ranks[$atlet['id_atlet']]['nama_atlet'] = $atlet['nama_atlet'];
	$ranks[$atlet['id_atlet']]['nilai'] = $nilai_v;
	
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
		
		
		<!-- Step 4: Matriks Y ==================== -->
		<h3>Step 4: Matriks Y</h3>			
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
							echo round($matriks_y[$id_kriteria][$id_atlet], $digit);
							echo '</td>';
						endforeach;
						?>
					</tr>
				<?php endforeach; ?>	
			</tbody>
		</table>	
		
		
		<!-- Step 5.1: Solusi Ideal Positif ==================== -->
		<h3>Step 5.1: Solusi Ideal Positif (A<sup>+</sup>)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>					
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<td>
							<?php
							$id_kriteria = $kriteria['id_kriteria'];							
							echo round($solusi_ideal_positif[$id_kriteria], $digit);
							?>
						</td>
					<?php endforeach; ?>
				</tr>					
			</tbody>
		</table>
		
		<!-- Step 5.2: Solusi Ideal negative ==================== -->
		<h3>Step 5.2: Solusi Ideal Negatif (A<sup>-</sup>)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>					
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<td>
							<?php
							$id_kriteria = $kriteria['id_kriteria'];							
							echo round($solusi_ideal_negatif[$id_kriteria], $digit);
							?>
						</td>
					<?php endforeach; ?>
				</tr>					
			</tbody>
		</table>		
		
		<!-- Step 6.1: Jarak Ideal Positif ==================== -->
		<h3>Step 6.1: Jarak Ideal Positif (S<sub>i</sub>+)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>					
				<tr>
					<th class="super-top-left">ID Kreator</th>
					<th class="super-top-left">Nama Kreator</th>
					<th class="super-top-left">Jarak Ideal Positif</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($atlets as $atlet ): ?>
					<tr>
						<td><?php echo $atlet['nomor_atlet']; ?></td>
						<td><?php echo $atlet['nama_atlet']; ?></td>
						<td>
							<?php								
							$id_atlet = $atlet['id_atlet'];
							echo round($jarak_ideal_positif[$id_atlet], $digit);
							?>
						</td>						
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- Step 6.2: Jarak Ideal Negatif ==================== -->
		<h3>Step 6.2: Jarak Ideal Negatif (S<sub>i</sub>-)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>					
				<tr>
					<th class="super-top-left">ID Kreator</th>
					<th class="super-top-left">Nama Kreator</th>
					<th class="super-top-left">Jarak Ideal Negatif</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($atlets as $atlet ): ?>
					<tr>
						<td><?php echo $atlet['nomor_atlet']; ?></td>
						<td><?php echo $atlet['nama_atlet']; ?></td>
						<td>
							<?php								
							$id_atlet = $atlet['id_atlet'];
							echo round($jarak_ideal_negatif[$id_atlet], $digit);
							?>
						</td>						
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		
		<!-- Step 7: Perangkingan ==================== -->
		<?php
		$no = 1;		
		$sorted_ranks = $ranks;	
		
		// Sorting
		if(function_exists('array_multisort')):
			foreach ($sorted_ranks as $key => $row) {
				$nomor_atlet[$key]  = $row['nomor_atlet'];
				$nama_atlet[$key]  = $row['nama_atlet'];
				$nilai[$key] = $row['nilai'];
			}
			array_multisort($nilai, SORT_DESC, $nomor_atlet, SORT_ASC, $sorted_ranks);
		endif;
		?>		
		<h3>Step 7: Perangkingan (V)</h3>			
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