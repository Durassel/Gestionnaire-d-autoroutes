#------------------------------------------------------------
#        Script MySQL.
#------------------------------------------------------------


#------------------------------------------------------------
# Table: Troncon
#------------------------------------------------------------

CREATE TABLE Troncon(
        CodT Int NOT NULL AUTO_INCREMENT ,
        DuKm Int ,
        AuKm Int ,
        CodA Varchar (25) ,
        Num  Int ,
        PRIMARY KEY (CodT)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: Autoroute
#------------------------------------------------------------

CREATE TABLE Autoroute(
        CodA Varchar (25) NOT NULL ,
        DuKm Int ,
        AuKm Int ,
        PRIMARY KEY (CodA)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: Sortie
#------------------------------------------------------------

CREATE TABLE Sortie(
        Libelle  Varchar (25) NOT NULL ,
        Numero   Int NOT NULL AUTO_INCREMENT ,
        CodT     Int ,
        CodP     Int ,
        KmSortie Int ,
        PRIMARY KEY (Numero)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: Ville
#------------------------------------------------------------

CREATE TABLE Ville(
        CodP Int NOT NULL ,
        Nom  Varchar (25) ,
        PRIMARY KEY (CodP)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: SCA
#------------------------------------------------------------

CREATE TABLE SCA(
        Code          Int NOT NULL AUTO_INCREMENT ,
        Nom           Varchar (25) ,
        CA            Int ,
        Duree_Contrat Int ,
        PRIMARY KEY (Code)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: Peage
#------------------------------------------------------------

CREATE TABLE Peage(
        CodePeage Int NOT NULL AUTO_INCREMENT ,
        PGDuKm    Int ,
        PGAuKm    Int ,
        Tarif     Float ,
        CodA      Varchar (25) ,
        Code      Int ,
        PRIMARY KEY (CodePeage)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: Registre Fermeture
#------------------------------------------------------------

CREATE TABLE Registre_Fermeture(
        Num        Int NOT NULL AUTO_INCREMENT ,
        DateDebut  Date ,
        DateFin    Date ,
        Descriptif Varchar (150) ,
        PRIMARY KEY (Num)
)ENGINE=InnoDB;

#------------------------------------------------------------
# Table: Utilisateur
#------------------------------------------------------------

CREATE TABLE Utilisateur(
        Id        Int NOT NULL AUTO_INCREMENT ,
        Nom       Varchar (150) ,
        Prenom    Varchar (150) ,
        Email     Varchar (150) ,
        Password  Varchar (150) ,
        Statut    Varchar (150) ,
        PRIMARY KEY (Id)
)ENGINE=InnoDB;

ALTER TABLE Troncon ADD CONSTRAINT FK_Troncon_CodA FOREIGN KEY (CodA) REFERENCES Autoroute(CodA);
ALTER TABLE Troncon ADD CONSTRAINT FK_Troncon_Num FOREIGN KEY (Num) REFERENCES Registre_Fermeture(Num);
ALTER TABLE Sortie ADD CONSTRAINT FK_Sortie_CodT FOREIGN KEY (CodT) REFERENCES Troncon(CodT);
ALTER TABLE Peage ADD CONSTRAINT FK_Peage_CodA FOREIGN KEY (CodA) REFERENCES Autoroute(CodA);
ALTER TABLE Peage ADD CONSTRAINT FK_Peage_Code FOREIGN KEY (Code) REFERENCES Sca(Code);
ALTER TABLE Sortie ADD CONSTRAINT FK_Peage_CodP FOREIGN KEY (CodP) REFERENCES Ville(CodP);

INSERT INTO `ville` (`CodP`, `Nom`) VALUES
(13000, 'Marseille'),
(33000, 'Bordeaux'),
(59000, 'Lille'),
(69000, 'Lyon'),
(75000, 'Paris'),
(94000, 'Nogent sur marne');

INSERT INTO `autoroute` (`CodA`, `DuKm`, `AuKm`) VALUES
('A1', 1, 211),
('A104', 1, 27),
('A3', 1, 18),
('A4', 1, 482),
('A5', 1, 225),
('A6', 1, 466),
('A7', 1, 312),
('A8', 1, 224),
('A86', 1, 80);

INSERT INTO `registre_fermeture` (`Num`, `DateDebut`, `DateFin`, `Descriptif`) VALUES
(2, '2017-05-23', '2017-05-29', 'remise en etat de la chaussee');

INSERT INTO `sca` (`Code`, `Nom`, `CA`, `Duree_Contrat`) VALUES
(1, 'ADELAC', 250000000, 2),
(2, 'APRR', 2000000000, 3),
(3, 'ALBEA', 1500000000, 1),
(4, 'VINCI', 3000000000, 3),
(5, 'ALICORNE', 1000000000, 3),
(6, 'ATMB', 1500000000, 10),
(7, 'dzfevbg', 562654, 2),
(8, 'dzfevbg', 562654, 2);

INSERT INTO `peage` (`CodePeage`, `PGDuKm`, `PGAuKm`, `Tarif`, `CodA`) VALUES
(1, 0, 75, 16.1, 'A1'),
(2, 75, 150, 16.1, 'A3'),
(3, 150, 225, 10, 'A6'),
(4, 50, 150, 12.5, 'A8'),
(5, 5, 10, 20,'A86'),
(6, 1, 50, 16, 'A3');

INSERT INTO `troncon` (`CodT`, `DuKm`, `AuKm`, `CodA`, `Num`) VALUES
(1, 1, 100, 'A4', NULL),
(2, 100, 200, 'A4', NULL),
(3, 200, 300, 'A4', NULL),
(4, 300, 400, 'A4', NULL),
(5, 400, 482, 'A4', NULL),
(6, 150, 175, 'A5', NULL),
(7, 150, 175, 'A4', 2);

INSERT INTO `sortie` (`Libelle`, `Numero`, `KmSortie`, `CodT`, `CodP`) VALUES
('Argency', 1, 318, 4, '13000'),
('Châlons-en-Champagne', 2, 171, 2, '59000'),
('Charenton-Le-Pont', 3, 2, 2, '69000'),
('Ivry-sur-Seine', 4, 2, 1, '94000'),
('Lizy-sur-Ourcq', 5, 65, 1, '13000'),
('Marange-Silvange', 6, 311, 4, '94000'),
('Noisy-Le-Grand', 7, 12, 1, '94000'),
('Paris', 8, 155, 5, '75000'),
('Strasbourg', 9, 480, 5, '59000'),
('Villejuif', 10, 35, 3, '94000');