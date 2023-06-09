<?php
include_once __DIR__ . '/../src/m/connect.php';

if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id_formateur'])) {
    header("Location: connexion.php?type=info&message=Session+expirée");
    die;
}

if (isset($_SESSION['utilisateur']['id_stagiaire']) && $_SESSION['utilisateur']['id_stagiaire'] > 0 && (!isset($_GET["page"]) || (isset($_GET["page"]) && $_GET["page"] !== "formation"))) {
    header("Location: ?page=formation");
    die;
}

if (isset($_GET["page"]) && !empty($_GET["page"])) {
    if (isset($db) && !empty($db)) {
        $page = "notfound.html";
        // if(isset($_SESSION['utilisateur'])) {
        switch ($_GET["page"]) {
            case "formation":
                $page = "formation/index.php";
                break;
            case "admin":
                $page = "admin/index.php";
                break;
            case "stage":
                $page = "stage/index.php";
                break;
            case "titre":
                $page = "titre/index.php";
                break;
            case "mon-compte":
                $_GET['form'] = "mon-compte";
                $page = "formulaire.php";
                break;
            case "ajouter_referent":
                $_GET['form'] = "ajouter_referent";
                $page = "formulaire.php";
                break;
            case "ajouter_stagiaire":
                $_GET['form'] = "ajouter_stagiaire";
                $page = "formulaire.php";
                break;
            case "ajouter_document":
                $_GET['form'] = "ajouter_document";
                $page = "formulaire.php";
                break;
        }
        if ($_SESSION['utilisateur']['id_formateur'] > 0 && $page !== "notfound.html") {
            $req = $db->prepare('REPLACE INTO historiques(id_formateur, page_visitee, page_nom, ip_visiteur, date_visite)
                                VALUES(:id_formateur, :page_visitee, :page_nom, :ip_visiteur, NOW());');
            $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['id_formateur'], FILTER_VALIDATE_INT));
            $req->bindValue(':page_visitee', '?page=' . filter_var($_GET['page'], FILTER_SANITIZE_SPECIAL_CHARS));
            $req->bindValue(':page_nom', ucfirst(filter_var($_GET['page'], FILTER_SANITIZE_SPECIAL_CHARS)));
            $req->bindValue(':ip_visiteur', filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP));
            $req->execute();
        }
        // } else {
        //     $page = "connexion.php";
        // }
    } else {
        $page = "maintenance.php";
    }
    include __DIR__ . "/" . $page;
    die;
}

$req = $db->prepare("SELECT * FROM sessions WHERE id_formateur=:id_formateur;");
$req->bindValue(":id_formateur", filter_var($_SESSION['utilisateur']['id_formateur'], FILTER_VALIDATE_INT));
$req->execute();
$sessions = $req->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['form_filter_session'])) {
    $_SESSION['filtres']['id_session'] = filter_var($_POST['form_filter_session'], FILTER_VALIDATE_INT);
} else {
    $_SESSION['filtres']['id_session'] = -1;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff"/>
    <meta name="description" content="ERP de l'ADRAR. Connectez-vous pour en voir plus.">
    <script src="https://kit.fontawesome.com/b478fcca05.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/_reset.css?v=<?= uniqid() ?>">
    <link rel="stylesheet" href="css/_style.css?v=<?= uniqid() ?>">
    <link rel="stylesheet" href="css/index.css?v=<?= uniqid() ?>">
    <title>Accueil - ERP</title>
</head>

<body>
    <div class="container">
        <div class="sidenav">
            <?= file_get_contents('sidenav.php') ?>
        </div>
        <div class="main">
            <div class="box-1">
                <form method="post" onchange="this.submit();">
                    <select name="form_filter_session">
                        <option value="-1">Tout le secteur</option>
                        <option value="0" <?= (empty($_SESSION['filtres']['id_session']) ? " selected" : "") ?>>Toutes mes sessions</option>
                        <?php if (!empty($sessions)) {
                            foreach ($sessions as $session) { ?>
                                <option value="<?= $session['id_session'] ?>" <?= (isset($_SESSION['filtres']['id_session']) && $_SESSION['filtres']['id_session'] == $session['id_session'] ? " selected" : "") ?>><?= $session['nom_session'] ?></option>
                        <?php }
                        } ?>
                    </select>
                </form>
                <input type="search" name="search" placeholder="Rechercher...">
            </div>
            <div class="box-2">
                <div class="box">
                    <a href="index.php?page=ajouter_referent">
                        <div class="contenu">
                            <h2>Ajouter un référent</h2>
                        </div>
                    </a>
                </div>
                <div class="box">
                    <a href="index.php?page=ajouter_stagiaire">
                        <div class="contenu">
                            <h2>Ajouter un stagiaire</h2>
                        </div>
                    </a>
                </div>
                <div class="box">
                    <a href="index.php?page=ajouter_tuteur">
                        <div class="contenu">
                            <h2>Ajouter un tuteur</h2>
                        </div>
                    </a>
                </div>
                <div class="box">
                    <a href="index.php?page=ajouter_document">
                        <div class="contenu">
                            <h2>Ajouter un document</h2>
                        </div>
                    </a>
                </div>
            </div>
            <div class="box-3">
                <div class="historique">
                    <div class="contenu">
                        <div class="card-title">
                            <h2>Historique</h2>
                            <hr class="small-separator">
                        </div>
                        <div class="card-content">
                            <?php
                            $req = $db->prepare('SELECT * FROM historiques WHERE id_formateur=:id_formateur ORDER BY date_visite DESC LIMIT 3;');
                            $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['id_formateur'], FILTER_VALIDATE_INT));
                            $req->execute();
                            $historiques = $req->fetchAll(PDO::FETCH_ASSOC);
                            $req->closeCursor();
                            if (!empty($historiques)) {
                                foreach ($historiques as $historique) { ?>
                                    <p><a href="<?= $historique['page_visitee'] ?>"><?= str_replace('_', ' un ', $historique['page_nom']) ?></a></p>
                                <?php
                                }
                            } else { ?>
                                <p>Aucun historique disponible</p>
                            <?php
                            } ?>
                        </div>
                    </div>
                </div>
                <div class="convention">
                    <div class="contenu">
                        <div class="card-title">
                            <h2>Convention de stage</h2>
                            <hr class="small-separator">
                        </div>
                        <div class="card-content ratios">
                            <span id="ratio_convention_de_stage"></span>
                        </div>
                    </div>
                </div>
                <div class="attestation">
                    <div class="contenu">
                        <div class="card-title">
                            <h2>Attestations de stage</h2>
                            <hr class="small-separator">
                        </div>
                        <div class="card-content ratios">
                            <span id="ratio_attestation_de_stage"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-4">
                <div class="modules">
                    <div class="contenu">
                        <div class="card-title">
                            <h2>Modules</h2>
                            <hr class="small-separator">
                        </div>
                        <div class="card-content">
                            <div class="modules-box">
                                <div class="module" id="module-administration">
                                    <h2>Administration</h2>
                                    <p>Panel d'administration des formateurs, tuteurs, stagiaires, documents (ajout/édition/suppression).</p>
                                </div>
                                <div class="module" id="module-formation">
                                    <h2>Formation</h2>
                                    <p>Petit module sur lequel vous pouvez rendre vos cours disponibles pour vos stagiaires et faire des quiz.</p>
                                </div>
                                <div class="module" id="module-stages">
                                    <h2>Stage</h2>
                                    <p>Cette page reprend un tableau de suivi de stage filtrable avec la posibilité de télécharge le tableau et de lancer/relancer la demande de documents (pré-remplis et pré-signés) auprès du tuteur.</p>
                                </div>
                                <div class="module" id="module-titres">
                                    <h2>Titre</h2>
                                    <p>Cette page vous permet de générer un document pour un stagiaire donné. Vous avez également la possibilité d'ajouter toutes les informations manuellement pour chaque document.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-5">
                <div class="evaluation">
                    <div class="contenu">
                        <div class="card-title">
                            <h2>Évaluations de stage</h2>
                            <hr class="small-separator">
                        </div>
                        <div class="card-content ratios">
                            <span id="ratio_evaluation_de_stage"></span>
                        </div>
                    </div>
                </div>
                <div class="presence">
                    <div class="contenu">
                        <div class="card-title">
                            <h2>Présence en entreprise</h2>
                            <hr class="small-separator">
                        </div>
                        <div class="card-content ratios">
                            <span id="ratio_presence_en_entreprise"></span>
                        </div>
                    </div>
                </div>
                <div class="titre hidden">
                    <div class="contenu">
                        <div class="card-title">
                            <h2>Titre</h2>
                            <hr class="small-separator">
                        </div>
                        <div class="card-content">
                        </div>
                    </div>
                </div>
                <div class="titre hidden">
                    <div class="contenu">
                        <div class="card-title">
                            <h2>Titre</h2>
                            <hr class="small-separator">
                        </div>
                        <div class="card-content">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="./js/index.js?v=<?= uniqid() ?>"></script>
    <script src="https://cdn.websitepolicies.io/lib/cconsent/cconsent.min.js" defer></script>
    <script>
        window.addEventListener("load", function() {
            window.wpcb.init({
                "border": "thin",
                "corners": "small",
                "colors": {
                    "popup": {
                        "background": "#D9D9D9",
                        "text": "#000000",
                        "border": "#45BEEB"
                    },
                    "button": {
                        "background": "#45BEEB",
                        "text": "#ffffff"
                    }
                },
                "content": {
                    "href": "<?= $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] ?>/erp/public/cookies.php",
                    "message": "Ce site utilise des cookies pour vous assurer une meilleure navigation. ",
                    "link": "En savoir plus",
                    "button": "Accepter"
                }
            })
        });
    </script>
</body>

</html>