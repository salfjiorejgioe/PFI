use dbdarquest2;

CREATE TABLE Joueurs (
    alias VARCHAR(30) PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    MontantOr INT DEFAULT 5,
    MontantArgent INT DEFAULT 10,
    MontantBronze INT DEFAULT 20,
    EstMage BOOLEAN,
    idUser INT,
    NbQuetesCompletes INT,
    roles INT NOT NULL DEFAULT 0
);

CREATE TABLE Items (
    id INT PRIMARY KEY,
    nomItem VARCHAR(255) NOT NULL,
    descriptionItem TEXT,
    typeITEM VARCHAR(50),
    MontantEnStock INT,
    prixOr INT,
    prixArgent INT,
    prixBronze INT,
    NbEtoiles INT,
    EvaluationJoueurs VARCHAR(255)
);

CREATE TABLE Quetes (
    id INT PRIMARY KEY,
    nomQuete VARCHAR(255) NOT NULL,
    descriptionQuete TEXT,
    Difficulte VARCHAR(50),
    recompenseOr INT,
    recompenseArgent INT,
    recompenseBronze INT,
    EstMagique BOOLEAN
);

CREATE TABLE Inventaire (
    idInventaire INT PRIMARY KEY,
    aliasJoueur VARCHAR(30) NOT NULL,
    idItem INT NOT NULL,
    FOREIGN KEY (aliasJoueur) REFERENCES Joueurs(alias),
    FOREIGN KEY (idItem) REFERENCES Items(id)
);

CREATE TABLE Joueurs_Items (
    aliasJoueur VARCHAR(30) NOT NULL,
    idItem INT NOT NULL,
    PRIMARY KEY (aliasJoueur, idItem),
    FOREIGN KEY (aliasJoueur) REFERENCES Joueurs(alias),
    FOREIGN KEY (idItem) REFERENCES Items(id)
);

CREATE TABLE Joueurs_Quetes (
    aliasJoueur VARCHAR(30) NOT NULL,
    idQuete INT NOT NULL,
    PRIMARY KEY (aliasJoueur, idQuete),
    FOREIGN KEY (aliasJoueur) REFERENCES Joueurs(alias),
    FOREIGN KEY (idQuete) REFERENCES Quetes(id)
);



