<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
     <main>
            <h1>Marché Darquest</h1>

            <div>Entrez vos informations de création de compte.</div>

            <div class="col-md-4 mx-auto">                         

                <form method="post" novalidate>

                    <div class="mb-3">
                        <label for="email" class="form-label"><span class="text-danger">* </span>Courriel</label>
                        <input name="email" type="email" class="form-control" id="email" aria-describedby="emailHelp" value="<?= htmlspecialchars($email) ?>" autofocus>
                        <div id="emailHelp" class="form-text text-danger"><?= $messages['email'] ?? '' ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label"><span class="text-danger">* </span>Mot de passe</label>
                        <input name="password" type="password" class="form-control" id="empasswordail" aria-describedby="passwordHelp">
                        <div id="passwordHelp" class="form-text text-danger"><?= $messages['password'] ?? '' ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="password2" class="form-label"><span class="text-danger">* </span>Confirmez le mot de passe</label>
                        <input name="password2" type="password" class="form-control" id="empasswordail2" aria-describedby="password2Help">
                        <div id="passwordHelp2" class="form-text text-danger"><?= $messages['password2'] ?? '' ?></div>
                    </div>

                    <div>
                        Le mot de passe doit contenir : 
                        <ul>
                            <li>au moins une lettre minuscule</li>
                            <li>au moins une lettre majuscule</li>
                            <li>au moins un chiffre</li>
                            <li>au moins un symbole @#-_$%^&+=§!?</li>
                        </ul>

                    </div>

                    <div class="py-3 text-danger">* Champs requis</div>
                    
                    <button type="submit" class="btn btn-primary">Envoyer</button>

                </form>

                <div id="global-message" class="my-3 <?= $globalMessageColor ?>"><?= $messages['global'] ?? '' ?></dib>

                <div class="py-3"><a href="login.php">Connexion à un compte</a></div>

            </div>
            <!--Formulaire authenfification-Authentication form-->

        </main>
</body>
</html>