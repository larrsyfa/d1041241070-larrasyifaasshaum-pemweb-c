USE reviewbuku;

ALTER TABLE books
  ADD COLUMN jumlah_halaman INT UNSIGNED NULL AFTER cover,
  ADD COLUMN tahun_terbit SMALLINT UNSIGNED NULL AFTER jumlah_halaman,
  ADD COLUMN penerbit VARCHAR(100) NULL AFTER tahun_terbit;

ALTER TABLE users
  ADD COLUMN bio TEXT NULL AFTER role;

CREATE TABLE IF NOT EXISTS review_likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  review_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review_user_like (review_id, user_id),
  CONSTRAINT fk_like_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
  CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

USE reviewbuku;

INSERT INTO books (
  judul,
  penulis,
  genre,
  sinopsis,
  cover,
  jumlah_halaman,
  tahun_terbit,
  penerbit
) VALUES
-- 1. Historical Fiction
(
  'Laskar Pelangi',
  'Andrea Hirata',
  'Historical Fiction',
  'Mengisahkan perjalanan sepuluh anak dari keluarga kurang mampu yang menempuh pendidikan di sebuah sekolah Muhammadiyah di Pulau Belitung dengan segala keterbatasan sarana dan fasilitas. Di tengah ancaman penutupan sekolah, kehadiran seorang guru yang berdedikasi serta semangat persahabatan dan mimpi besar mereka menjadi penggerak utama cerita.',
  NULL,
  529,
  2005,
  'Bentang Pustaka'
),
(
  'Cantik Itu Luka',
  'Eka Kurniawan',
  'Historical Fiction',
  'Menelusuri kisah lintas generasi di kota fiktif Halimunda yang memadukan nuansa realisme magis, sejarah kolonialisme, dan pergulatan batin para tokohnya. Cerita bermula dari sosok Dewi Ayu dan berlanjut ke anak-anak serta cucunya, memperlihatkan bagaimana luka sejarah, prasangka sosial, dan takdir keluarga saling memengaruhi.',
  NULL,
  504,
  2002,
  'Gramedia Pustaka Utama'
),
(
  'Gadis Kretek',
  'Ratih Kumala',
  'Historical Fiction',
  'Berlatar perjalanan industri kretek di Indonesia pada masa lalu hingga masa kini, novel ini merangkai kisah cinta, rahasia keluarga, dan jejak seorang perajin saus kretek berbakat bernama Jeng Yah. Pencarian atas sosok di balik resep istimewa tersebut membuka kembali benang merah masa lalu yang sempat tersembunyi.',
  NULL,
  376,
  2012,
  'Gramedia Pustaka Utama'
),

-- 2. Romance
(
  'Ayat-Ayat Cinta',
  'Habiburrahman El Shirazy',
  'Romance',
  'Mengikuti perjalanan Fahri, mahasiswa asal Indonesia yang menempuh studi pascasarjana di Universitas Al-Azhar, Mesir, dengan memegang teguh nilai keimanan dan kepedulian sosial. Di tengah kesibukan akademik dan kehidupan perantauan, Fahri dihadapkan pada dinamika perasaan, kesalahpahaman, serta ujian pernikahan dan keteguhan hati.',
  NULL,
  419,
  2004,
  'Republika'
),
(
  'Dilan: Dia adalah Dilanku Tahun 1990',
  'Pidi Baiq',
  'Romance',
  'Dikisahkan melalui sudut pandang kenangan Milea tentang pertemuannya dengan Dilan, siswa SMA di Bandung pada tahun 1990. Dengan gaya bertutur yang santai, puitis, dan penuh kejutan, Dilan mendekati Milea lewat cara-cara unik yang perlahan menumbuhkan kedekatan emosional di tengah dinamika masa remaja.',
  NULL,
  348,
  2014,
  'Pastel Books'
),
(
  'Hujan',
  'Tere Liye',
  'Romance',
  'Berlatar dunia masa depan ketika teknologi dan perubahan iklim ekstrem mengubah kehidupan manusia, novel ini mengikuti perjalanan Lail dan Esok yang dipertemukan oleh bencana besar. Keduanya menjalani proses pendewasaan, kehilangan, dan pencarian makna keikhlasan sambil belajar berdamai dengan kenangan masa lalu.',
  NULL,
  320,
  2016,
  'Gramedia Pustaka Utama'
),

-- 3. Fantasy
(
  'Bumi',
  'Tere Liye',
  'Fantasy',
  'Menceritakan Raib, remaja berusia lima belas tahun yang tampak seperti pelajar biasa namun memiliki kemampuan istimewa menghilang ketika marah atau berada dalam situasi tertentu. Bersama dua sahabatnya, Seli dan Ali, Raib terseret ke petualangan lintas dimensi dan dunia paralel yang penuh keajaiban serta ancaman baru.',
  NULL,
  440,
  2014,
  'Gramedia Pustaka Utama'
),
(
  'Bulan',
  'Tere Liye',
  'Fantasy',
  'Melanjutkan petualangan Raib, Seli, dan Ali setelah pengalaman mereka di dunia paralel sebelumnya. Kali ini mereka diajak melakukan perjalanan diplomatik dan petualangan ke klan Bulan dengan budaya, teknologi, dan tantangan yang berbeda, sambil menguji kerja sama serta keberanian mereka.',
  NULL,
  400,
  2015,
  'Gramedia Pustaka Utama'
),
(
  'Matahari',
  'Tere Liye',
  'Fantasy',
  'Bagian ketiga dalam rangkaian petualangan Raib, Seli, dan Ali yang membawa mereka menyingkap rahasia lebih dalam tentang klan Matahari dan kekuatan yang tersembunyi. Konflik yang semakin besar menuntut ketangguhan mental, persahabatan, serta keputusan penting dari tiap tokohnya.',
  NULL,
  400,
  2016,
  'Gramedia Pustaka Utama'
),

-- 4. Fiction
(
  'Negeri 5 Menara',
  'Ahmad Fuadi',
  'Fiction',
  'Mengisahkan Alif yang harus merantau jauh dari Sumatera Barat ke Pondok Madani di Ponorogo untuk melanjutkan pendidikan. Di lingkungan pesantren yang disiplin, ia bersama lima sahabat dari berbagai daerah menyusun mimpi besar melalui semboyan man jadda wajada dan saling menguatkan melewati kerasnya proses belajar.',
  NULL,
  424,
  2009,
  'Gramedia Pustaka Utama'
),
(
  'Sang Pemimpi',
  'Andrea Hirata',
  'Fiction',
  'Melanjutkan kisah perjuangan Ikal, Arai, dan Jimbron yang tumbuh dalam keterbatasan ekonomi di Belitong namun memiliki ambisi besar untuk mengubah nasib. Novel ini menyoroti semangat persaudaraan, kerasnya bekerja paruh waktu, serta tekad menempuh pendidikan tinggi hingga ke luar negeri.',
  NULL,
  292,
  2006,
  'Bentang Pustaka'
),
(
  'Orang-Orang Proyek',
  'Ahmad Tohari',
  'Fiction',
  'Menghadirkan potret realitas sosial dan moralitas di balik pelaksanaan sebuah proyek pembangunan infrastruktur daerah. Tokoh utamanya berhadapan dengan dilema antara idealisme profesional, tekanan birokrasi, dan praktik penyimpangan yang menguji integritas diri.',
  NULL,
  340,
  1995,
  'Yayasan Obor Indonesia'
),

-- 5. Mystery & Thriller
(
  'Laut Bercerita (Edisi Khusus)',
  'Leila S. Chudori',
  'Mystery & Thriller',
  'Mengangkat ketegangan investigasi batin dan pencarian jejak para aktivis mahasiswa yang hilang pada tahun 1998. Melalui sudut pandang keluarga yang ditinggalkan dan sahabat seperjuangan, cerita menyusun potongan ingatan serta misteri nasib para korban di tengah bayang-bayang represi.',
  NULL,
  379,
  2017,
  'Kepustakaan Populer Gramedia'
),
(
  'Aroma Karsa',
  'Dee Lestari',
  'Mystery & Thriller',
  'Berpusat pada pencarian pusaka botani mistis bernama Puspa Karsa yang diyakini menyimpan kekuatan luar biasa terkait indra penciuman. Perjalanan menelusuri jejak legenda, rahasia keluarga, dan intrik pihak-pihak yang memburu pusaka tersebut menghadirkan ketegangan serta teka-teki berlapis.',
  NULL,
  720,
  2018,
  'Bentang Pustaka'
),
(
  'Rindu',
  'Tere Liye',
  'Mystery & Thriller',
  'Berlatar perjalanan panjang kapal uap lintas samudra menuju Tanah Suci pada masa kolonial yang menampung beragam penumpang dengan latar belakang berbeda. Di balik rutinitas pelayaran, tersimpan rahasia masa lalu, konflik batin, dan misteri dendam yang perlahan terungkap sepanjang perjalanan.',
  NULL,
  544,
  2014,
  'Republika'
),

-- 6. Comedy
(
  'Kambing Jantan: Sebuah Catatan Harian Pelajar Bodoh',
  'Raditya Dika',
  'Comedy',
  'Mengangkat catatan keseharian penuh kelucuan dan kejujuran absurd tentang pengalaman kuliah di luar negeri, hubungan jarak jauh, serta kebiasaan sehari-hari yang serba canggung. Gaya humor observasionalnya membuat situasi sehari-hari yang sederhana terasa menghibur dan mudah diikuti.',
  NULL,
  220,
  2005,
  'GagasMedia'
),
(
  'Cinta Brontosaurus',
  'Raditya Dika',
  'Comedy',
  'Kumpulan cerita pendek bergaya komedi satir yang mengulas kisah percintaan, patah hati, dan keanehan karakter manusia dalam kehidupan sehari-hari. Tiap cerita dikemas ringan dengan sindiran jenaka terhadap klise romansa dan tingkah laku tokohnya.',
  NULL,
  180,
  2006,
  'GagasMedia'
),
(
  'Manusia Setengah Salmon',
  'Raditya Dika',
  'Comedy',
  'Menghadirkan refleksi komedi tentang proses beranjak dewasa, pindah rumah, dan cara berdamai dengan perubahan hidup yang tidak selalu sesuai rencana. Lewat anekdot keseharian yang kocak, buku ini memadukan tawa dengan perenungan ringan soal fase transisi.',
  NULL,
  248,
  2011,
  'GagasMedia'
),

-- 7. Self-Improvement
(
  'Atomic Habits',
  'James Clear',
  'Self-Improvement',
  'Membahas bagaimana perubahan kecil yang dilakukan secara konsisten setiap hari dapat menghasilkan dampak besar dalam jangka panjang. Buku ini menguraikan mekanisme terbentuknya kebiasaan—mulai dari isyarat, hasrat, respons, hingga ganjaran—serta strategi praktis membangun kebiasaan baik dan menghentikan kebiasaan buruk.',
  NULL,
  320,
  2018,
  'Gramedia Pustaka Utama'
),
(
  'Pengembangan Diri',
  'Dadang Sanjaya Atmaja',
  'Self-Improvement',
  'Menyajikan panduan praktis untuk mengenali potensi diri, mengatur arah tujuan hidup, dan meningkatkan kualitas kinerja pribadi maupun profesional. Pembahasannya menekankan langkah-langkah terukur agar pembaca dapat membangun kemandirian, disiplin, serta kesiapan menghadapi tantangan.',
  NULL,
  223,
  2025,
  'PT Kimhsafi Alung Cipta'
),
(
  'Berani Tidak Disukai',
  'Ichiro Kishimi & Fumitake Koga',
  'Self-Improvement',
  'Dikemas dalam bentuk dialog antara seorang pemuda dan seorang filsuf berdasarkan psikologi Adlerian, buku ini membahas kebebasan individu dari beban masa lalu dan ekspektasi orang lain. Pembaca diajak memahami tanggung jawab atas pilihan sendiri, relasi sehat, serta keberanian menjalani hidup saat ini.',
  NULL,
  350,
  2019,
  'Gramedia Pustaka Utama'
),

-- 8. Philosophy
(
  'Filosofi Teras',
  'Henry Manampiring',
  'Philosophy',
  'Memperkenalkan ajaran filsafat Stoa dengan bahasa yang membumi dan relevan untuk keseharian modern. Buku ini menjelaskan cara membedakan hal yang berada dalam kendali diri dan yang di luar kendali, sehingga membantu pembaca mengelola emosi negatif serta menjaga ketenangan batin.',
  NULL,
  344,
  2018,
  'Kompas'
),
(
  'Dunia Sophie',
  'Jostein Gaarder',
  'Philosophy',
  'Mengikuti kisah Sophie, seorang gadis remaja yang mulai menerima surat-surat misterius berisi pertanyaan mendasar tentang kehidupan dan keberadaan manusia. Melalui bimbingan korespondensi tersebut, pembaca diajak menelusuri sejarah pemikiran filsafat Barat dari zaman kuno hingga modern dengan alur cerita yang hidup.',
  NULL,
  800,
  1996,
  'Mizan'
),
(
  'Pengantar Filsafat: Aku Mengerti Membangun Diri',
  'Abu Amar',
  'Philosophy',
  'Mengulas konsep-konsep dasar filsafat sebagai sarana melatih cara berpikir kritis, reflektif, dan terstruktur dalam memahami realitas. Buku ini mengaitkan gagasan kefilsafatan dengan proses pembentukan karakter dan kesadaran diri dalam kehidupan sehari-hari.',
  NULL,
  346,
  2026,
  'Asta Cipta Mandiri'
),

-- 9. Biography & Memoir
(
  'Chairul Tanjung: Si Anak Singkong',
  'Chairul Tanjung',
  'Biography & Memoir',
  'Menceritakan perjalanan hidup dan jatuh bangun Chairul Tanjung sejak masa kecil hingga menempuh pendidikan tinggi dan membangun jejaring usaha. Kisah ini menyoroti kerja keras, ketangguhan menghadapi keterbatasan ekonomi, serta pelajaran kepemimpinan dari pengalaman nyata.',
  NULL,
  420,
  2012,
  'Kompas'
),
(
  'Habibie & Ainun',
  'Bacharuddin Jusuf Habibie',
  'Biography & Memoir',
  'Menuturkan kisah perjalanan hidup B.J. Habibie bersama istrinya, Hasri Ainun Habibie, mulai dari masa muda, studi dan karier di Jerman, hingga pengabdian bagi bangsa. Memoar ini menyoroti cinta, saling pengertian, serta keteguhan mendampingi satu sama lain dalam berbagai fase kehidupan.',
  NULL,
  323,
  2010,
  'The Habibie Center'
),
(
  'Rumi: Biografi Spiritual dan Pemikiran',
  'Haidar Bagir',
  'Biography & Memoir',
  'Menguraikan riwayat hidup, konteks zaman, dan pokok pemikiran Jalaluddin Rumi yang dikenal luas lewat karya sastra dan spiritualitasnya. Buku ini membantu pembaca memahami makna cinta, pencarian kebenaran, serta dimensi batin dalam karya-karya Rumi.',
  NULL,
  320,
  2018,
  'Mizan'
),

-- 10. Business & Entrepreneurship
(
  'Siap Menjadi StudentPreneur',
  'Rizal Fikri & Ferrial Pondrafi',
  'Business & Entrepreneurship',
  'Mengangkat kisah dan panduan praktis memulai wirausaha sejak masih berstatus pelajar atau mahasiswa dengan memanfaatkan peluang serta perkembangan teknologi digital. Buku ini mengulas pengalaman menghadapi kegagalan, membaca peluang pasar, dan mengelola usaha pada tahap awal.',
  NULL,
  173,
  2016,
  'Metagraf'
),
(
  'The Lean Startup',
  'Eric Ries',
  'Business & Entrepreneurship',
  'Menjelaskan pendekatan membangun produk dan bisnis baru melalui eksperimen cepat, pengukuran berbasis data, serta pembelajaran berkelanjutan dari umpan balik pelanggan. Konsep seperti produk minimum layak pakai (MVP) dan validasi hipotesis digunakan untuk mengurangi pemborosan sumber daya.',
  NULL,
  336,
  2011,
  'Crown Business'
),
(
  'Zero to One',
  'Peter Thiel & Blake Masters',
  'Business & Entrepreneurship',
  'Membahas cara menciptakan inovasi dan nilai baru yang benar-benar berbeda alih-alih sekadar meniru model bisnis yang sudah ada. Buku ini mengulas pentingnya keunikan produk, keunggulan jangka panjang, serta cara berpikir strategis dalam merancang perusahaan rintisan.',
  NULL,
  224,
  2014,
  'Crown Business'
);