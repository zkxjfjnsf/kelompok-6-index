<?php
require_once('includes/init.php');
$judul_page = 'Home';
require_once('template-parts/header.php');


echo "<br>";
echo "<p align='center'>";
echo "<img src='images/Youtube_logo.png' width='200' height='200'";
echo "</p>";
echo "<br>";
echo "<br>";
echo "<p align='center'>";
echo "<b>";
echo "<font size='6' color='#012367' family='helfetica'>Sistem Pendukung Keputusan Pemilihan Konten Kreator Terseru</font>";
echo "</b>";
echo "</p>";
echo "<p align='center'>";
echo "<b>";
echo "<font size='6' color='#012367' family='helfetica'>Menggunakan Metode TOPSIS Dan SAW</font>";
echo "</b>";
echo "<br>";
function tgl_indo($tanggal){
	$bulan = array (
		1 =>   'Januari',
		'Februari',
		'Maret',
		'April',
		'Mei',
		'Juni',
		'Juli',
		'Agustus',
		'September',
		'Oktober',
		'November',
		'Desember'
	);
	$pecahkan = explode('-', $tanggal);
	
	// variabel pecahkan 0 = tanggal
	// variabel pecahkan 1 = bulan
	// variabel pecahkan 2 = tahun
 
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}
echo "<br>";
echo "<font size='4'color='#012367' family='helfetica'>";
echo "Tanggal :";
echo "</font>";
echo "<br>";
echo "<font size='5'color='#012367' family='helfetica'>";
echo tgl_indo(date('Y-m-d'));
echo "</font>";
echo "<br>";
echo "<marquee behavior='alternate' scrollamount='20'>";
echo "<font size='4'color='#012367' family='helfetica'>";
echo "Silahkan Login dengan klik Tombol Login di atas";
echo "</font>";
echo "</marquee>";
echo "</p>";


require_once('template-parts/footer.php');
