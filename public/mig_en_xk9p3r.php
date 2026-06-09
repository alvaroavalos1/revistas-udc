<?php
// One-time migration: insert English translations for all 27 revistas.
// Protected by token. Delete this file after use.

if (($_GET['tok'] ?? '') !== 'xk9p3r2026') {
    http_response_code(403);
    exit('Forbidden');
}

$host   = getenv('MYSQLHOST');
$port   = getenv('MYSQLPORT') ?: '3306';
$dbname = getenv('MYSQLDATABASE');
$user   = getenv('MYSQLUSER');
$pass   = getenv('MYSQLPASSWORD');

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('DB connection failed: ' . $e->getMessage());
}

$existing = (int) $pdo->query('SELECT COUNT(*) FROM revistas_en')->fetchColumn();
if ($existing > 0) {
    exit("Already migrated: $existing rows in revistas_en. Nothing inserted.");
}

$rows = [
    [1,  'School of Medicine — Clinical Training Program',
     'Formal structure of hospital rotation cycles, undergraduate internships, and high-fidelity advanced clinical simulation laboratories.'],
    [2,  'School of Nursing — Community Practice',
     'Preventive healthcare intervention models, social diagnosis, and comprehensive care brigades in rural and indigenous communities of the state.'],
    [3,  'School of Psychology — Clinical Centers and Care',
     'Operational and supervisory framework of public community care centers offering therapy, diagnosis, and psychological counseling.'],
    [4,  'Veterinary Medicine and Animal Science — Tecomán Campus',
     'Technical operation of small and large animal hospitals, as well as the teaching stables and farms of the Tecomán campus.'],
    [5,  'FIME Coquimatlán — Heavy Workshop Facilities',
     'Inventory and instructional methodologies applied in thermo-fluids, computer-aided manufacturing (CNC), and electrical substation laboratories.'],
    [6,  'School of Telematics — Software Development',
     'Software engineering projects, information security, converged network management, and cloud-based solution deployment.'],
    [7,  'School of Chemical Sciences — Research',
     'Research lines in process engineering, heavy metal analysis, food quality control, and extractive metallurgy.'],
    [8,  'Electromechanical Engineering — Manzanillo Campus',
     'Electromechanical engineering applied to port gantry cranes, industrial refrigeration systems, and cogeneration plants.'],
    [9,  'School of Marine Sciences — Oceanography',
     'Planktonic sampling protocols, coastal dynamics, tidal current research, and biological studies along the Colima coastline.'],
    [10, 'Foreign Trade and Customs — Logistics',
     'Curriculum plan directly aligned with Mexican customs legislation, international trade procedures, and port supply chain management.'],
    [11, 'Vessel Infrastructure and Marine Sampling',
     'Specifications and equipment of coastal monitoring stations, research vessels, and wet laboratories for marine research.'],
    [12, 'School of Law — Free Legal Aid Clinics',
     'Organizational structure of legal clinics and social assistance offices where students handle real oral and civil proceedings.'],
    [13, 'School of Economics — Market Analysis',
     'Predictive models for consumer price baskets, microeconomic analysis, and the financial impact of the port on the state\'s Gross Domestic Product.'],
    [14, 'School of Political and Social Sciences',
     'Public opinion studies, regional electoral behavior, municipal governance, and civic participation dynamics in western Mexico.'],
    [15, 'FALCOM — Letters and Journalism',
     'In-house news agencies, university radio and television production, editorial laboratories, and literary creation workshops.'],
    [16, 'School of Architecture and Design — Workshops',
     'Sustainable urban planning programs, three-dimensional volumetric modeling workshops, and industrial product design using local materials.'],
    [17, 'School of Arts — Music and Dance',
     'Advanced curricula in concert instrumental performance, visual arts, and the rigor of contemporary and folk stage dance.'],
    [18, 'School of Sciences — Physics and Mathematics',
     'Research lines in particle physics, statistical mechanics, and the training of pure scientists integrated into global academic networks.'],
    [19, 'Biological and Agricultural Sciences — Tecomán Campus',
     'Integrated phytosanitary pest management (HLB in citrus crops), bovine reproductive biotechnology, and regional agricultural soil optimization.'],
    [20, 'FCA Colima — Accounting and Business Administration',
     'Corporate academic programs: tax auditing, business consulting, global corporate finance, and human capital management.'],
    [21, 'FCA Manzanillo — Management and Business',
     'Specialization in tourism and hotel administration, customs tax regimes, and business intermediation in Asian markets.'],
    [22, 'University Center for Volcanological Research',
     'Seismic and telemetric monitoring network of the Colima Volcano, geochemical gas analysis, and civil risk map generation.'],
    [23, 'Center for Social Research — CUIS',
     'Multidisciplinary projects on return migration dynamics, gender studies, equity, and sustainable urban development.'],
    [24, 'Library Network and Knowledge Access',
     'Technological architecture of the SIABUC software (developed by UdeC) and indexing catalogs for access to global scientific databases.'],
    [25, 'General Coordination of Information Technologies',
     'Administration of the university\'s fiber optic backbone network, the EDUC virtual learning platform, and supercomputing servers.'],
    [26, 'Organic Law and General Statutes — UdeC',
     'Normative analysis of university autonomy granted by Congress, executive powers, and the rights and obligations of the university community.'],
    [27, 'University Council — Supreme Governing Body',
     'Parity composition of the Supreme Governing Body: representation of democratically elected directors, professors, and students.'],
];

$stmt = $pdo->prepare(
    'INSERT INTO revistas_en (revista_id, subida_por, titulo, descripcion, estado, publicada_en)
     VALUES (?, 1, ?, ?, "publicada", NOW())'
);

$inserted = 0;
$errors   = [];
foreach ($rows as [$id, $titulo, $desc]) {
    try {
        $stmt->execute([$id, $titulo, $desc]);
        $inserted++;
    } catch (PDOException $e) {
        $errors[] = "revista_id=$id: " . $e->getMessage();
    }
}

header('Content-Type: text/plain; charset=utf-8');
echo "Inserted: $inserted / " . count($rows) . " rows into revistas_en.\n";
if ($errors) {
    echo "\nErrors:\n" . implode("\n", $errors) . "\n";
} else {
    echo "No errors. Migration complete.\n";
}
