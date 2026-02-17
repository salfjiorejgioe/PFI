CREATE DATABASE DarQuest;
Use DarQuest;


CREATE TABLE Joueurs (
    alias Unique NOT NULL,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    MontantOr INT DEFAULT 5,
    MontantArgent INT DEFAULT 10,
    MontantBronze INT DEFAULT 20,
    EstMage BOOLEAN,
    idUser INT,
    QuetesCompletes INT
    role int not null default 0;
    
)

CREATE TABLE Items (
    id INT PRIMARY KEY,
    nomItem VARCHAR(255) NOT NULL,
    descriptoinItem TEXT,
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
    idJoueur INT,
    idItem INT,
    FOREIGN KEY (idJoueur) REFERENCES Joueurs(alias),
    FOREIGN KEY (idItem) REFERENCES Items(id)
);

CREATE TABLE Joueurs_Items (
    idJoueur INT,
    idItem INT,
    PRIMARY KEY (idJoueur, idItem),
    FOREIGN KEY (idJoueur) REFERENCES Joueurs(alias),
    FOREIGN KEY (idItem) REFERENCES Items(id)
);
CREATE TABLE Joueurs_Quetes (
    idJoueur INT,
    idQuete INT,
    PRIMARY KEY (idJoueur, idQuete),
    FOREIGN KEY (idJoueur) REFERENCES Joueurs(alias),
    FOREIGN KEY (idQuete) REFERENCES Quetes(id)
);
