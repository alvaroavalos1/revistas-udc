<?php
// One-time migration: replace revistas_en with DeepL translations.
// Protected by token. Delete this file after use.

if (($_GET['tok'] ?? '') !== 'deepl2026xk') {
    http_response_code(403);
    exit('Forbidden');
}

$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT') ?: '3306';
$db   = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('DB error: ' . $e->getMessage());
}

// DeepL-translated rows: [revista_id, titulo, descripcion, publicada_en]
$rows = [
    [1,  'School of Medicine — Clinical Program',
     'Formal structure of hospital rotation programs, undergraduate clinical rotations, and high-fidelity advanced clinical simulation labs.',
     '2026-06-05 18:35:36'],
    [2,  'School of Nursing — Community Practice',
     'Intervention models for preventive health care, social assessment, and comprehensive care teams in rural and indigenous communities across the state.',
     '2026-06-05 18:35:36'],
    [3,  'School of Psychology — Clinics and Care',
     'Operational and oversight framework for community-based centers open to the public for therapy, diagnosis, and psychological counseling.',
     '2026-06-05 18:35:36'],
    [4,  'Veterinary Medicine and Animal Science, Tecomán',
     'Technical operations at the small- and large-animal hospitals, as well as the stables and educational farms on the Tecomán campus.',
     '2026-06-05 18:35:36'],
    [5,  'FIME Coquimatlán — Heavy Machinery Workshops',
     'Overview and teaching methodologies used in the thermo-fluid, computer-numerical-control (CNC), and electrical substation laboratories.',
     '2026-06-05 18:35:36'],
    [6,  'School of Telematics — Software Development',
     'Software engineering projects, information security, converged network management, and cloud solution deployment.',
     '2026-06-05 18:35:36'],
    [7,  'School of Chemical Sciences — Research',
     'Research areas in process engineering, heavy metal analysis, food quality control, and extractive metallurgy.',
     '2026-06-05 18:35:36'],
    [8,  'Electromechanical Engineering in Manzanillo',
     'Focus on electromechanical engineering for port gantry cranes, industrial refrigeration systems, and cogeneration plants.',
     '2026-06-05 18:35:36'],
    [9,  'School of Marine Sciences — Oceanography',
     'Plankton sampling protocols, coastal dynamics, tidal currents, and biological research along the Colima coast.',
     '2026-06-05 18:35:36'],
    [10, 'Foreign Trade and Customs — Logistics',
     'A curriculum directly linked to Mexican customs regulations, international trade procedures, and the port supply chain.',
     '2026-06-05 18:35:36'],
    [11, 'Vessel Infrastructure and Sampling',
     'Specifications and equipment for coastal monitoring stations, boats, and wet labs used in marine research.',
     '2026-06-05 18:35:36'],
    [12, 'Law School — Free Legal Services',
     'Organizational structure of legal clinics and social service law firms where students handle actual oral and civil trials.',
     '2026-06-05 18:35:36'],
    [13, 'School of Economics — Market Analysis',
     'Predictive models of basic food baskets, microeconomic analysis, and the financial impact of the port on the state’s gross domestic product.',
     '2026-06-05 18:35:36'],
    [14, 'School of Political and Social Sciences',
     'Public opinion surveys, regional voting patterns, municipal governance, and the dynamics of citizen participation in the western region.',
     '2026-06-05 18:35:36'],
    [15, 'FALCOM — Literature and Journalism',
     'On-campus news agencies, university radio and television production, publishing labs, and creative writing workshops.',
     '2026-06-05 18:35:36'],
    [16, 'School of Architecture and Design — Workshops',
     'Sustainable urban planning, workshops on 3D volumetric modeling, and industrial product design using local materials.',
     '2026-06-05 18:35:36'],
    [17, 'School of the Arts — Music and Dance',
     'Advanced curriculum programs in concert instrumental performance, visual arts, and the technical demands of contemporary and folk dance.',
     '2026-06-05 18:35:36'],
    [18, 'School of Sciences — Physics and Mathematics',
     'Research areas in particle physics, statistical mechanics, and the training of basic scientists who are part of global networks.',
     '2026-06-05 18:35:36'],
    [19, 'Biological and Agricultural Sciences, Tecomán',
     'Integrated pest management (HLB in citrus), bovine reproductive biotechnology, and optimization of regional agricultural soil.',
     '2026-06-05 18:35:36'],
    [20, 'FCA Colima — Accounting and Administration',
     'Corporate academic programs: tax auditing, business consulting, global corporate finance, and human capital management.',
     '2026-06-05 18:35:36'],
    [21, 'FCA Manzanillo — Management and Business',
     'Specialization in tourism and hospitality management, customs and tax regulations, and business brokerage in Asian markets.',
     '2026-06-05 18:35:36'],
    [22, 'University Center for Volcanological Research',
     'Seismic and telemetric monitoring network for the Volcán de Fuego de Colima, geochemical analysis of gases, and creation of civil risk maps.',
     '2026-06-05 18:35:36'],
    [23, 'Center for Social Research — CUIS',
     'Multidisciplinary projects on return migration dynamics, gender studies, equity, and sustainable urban development.',
     '2026-06-05 18:35:36'],
    [24, 'Library Network and Access to Knowledge',
     'The technological architecture of the SIABUC software (developed by the University of Colima) and indexing catalogs for accessing global scientific databases.',
     '2026-06-05 18:35:36'],
    [25, 'Office of Information Technologies',
     "Management of the university's fiber-optic backbone network, the EDUC virtual platform, and the supercomputing servers.",
     '2026-06-05 18:35:36'],
    [26, 'Organic Law and General Statutes of the University of Colima',
     'A legal analysis of the university autonomy granted by Congress, the powers of the executive branch, and the rights and obligations of the university community.',
     '2026-06-05 18:35:36'],
    [27, 'University Council — Highest Governing Body',
     'Equal representation on the highest governing body: representation of democratically elected directors, professors, and students.',
     '2026-06-05 18:35:36'],
];

$pdo->exec('DELETE FROM revistas_en');

$stmt = $pdo->prepare(
    'INSERT INTO revistas_en (revista_id, subida_por, titulo, descripcion, estado, publicada_en)
     VALUES (?, 1, ?, ?, "publicada", ?)'
);

$inserted = 0;
$errors   = [];
foreach ($rows as [$id, $titulo, $desc, $fecha]) {
    try {
        $stmt->execute([$id, $titulo, $desc, $fecha]);
        $inserted++;
    } catch (PDOException $e) {
        $errors[] = "id=$id: " . $e->getMessage();
    }
}

header('Content-Type: text/plain; charset=utf-8');
echo "Deleted old rows. Inserted: $inserted / " . count($rows) . "\n";
if ($errors) {
    echo "Errors:\n" . implode("\n", $errors) . "\n";
} else {
    echo "No errors. DeepL migration complete.\n";
}
