<?php
error_reporting(E_ALL & ~E_NOTICE);
include './vendor/autoload.php';

$conn = mysqli_connect('localhost', 'root', 'root', 'sindikker_nlp') or die('Koneksi Gagal');
$sql = "select * from program_studi_kbji_skor";
$query = mysqli_query($conn, $sql);

echo 'Load text ... ';
$data_set = mysqli_fetch_all($query, MYSQLI_ASSOC);
$data_set_count = count($data_set);
mysqli_free_result($query);
echo "OK\n";

$jw_compare = new TextAnalysis\Comparisons\JaroWinklerComparison();

for ($i = 0; $i < $data_set_count; $i++)
{
	$id				= $data_set[$i]['id'];
	$program_studi	= $data_set[$i]['program_studi_nama'];
	$pekerjaan		= $data_set[$i]['kbji_nama'];
	
	$program_studi_tokens	= tokenize($program_studi);
	$pekerjaan_tokens		= tokenize($pekerjaan);
	
	$batas_skor				= 0.8;
	$nilai_cukup			= 0;
	$jumlah_cukup			= 0;
	$nilai_tdk_cukup_tertinggi = 0;
	
	for ($i_ps = 0; $i_ps < count($program_studi_tokens); $i_ps++)
	{
		for ($i_p = 0; $i_p < count($pekerjaan_tokens); $i_p++)
		{
			$jw_result = $jw_compare->similarity($program_studi_tokens[$i_ps], $pekerjaan_tokens[$i_p]);

			if ($jw_result > $batas_skor)
			{
				$nilai_cukup += $jw_result;
				$jumlah_cukup++;
			}
			else
			{
				if ($nilai_tdk_cukup_tertinggi < $jw_result)
				{
					$nilai_tdk_cukup_tertinggi = $jw_result;
				}
			}
		}
	}

	if ($jumlah_cukup > 0)
	{
		$hasil_akhir = $nilai_cukup / $jumlah_cukup;
	}
	else
	{
		$hasil_akhir = $nilai_tdk_cukup_tertinggi;
	}
	
	echo '[' . ($i + 1) . '/' . $data_set_count . '] ' . $program_studi . ' -- ' . $pekerjaan . ' => ' . $hasil_akhir . "\n";
	
	mysqli_query($conn, "update program_studi_kbji_skor set skor = {$hasil_akhir} where id = {$id}");
}

echo "Selesai.";