<?php

namespace App\Entity;

use App\Repository\TournoiRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Equipe;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TournoiRepository::class)]
class Tournoi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du tournoi ne peut pas être vide.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $nom = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
    private ?\DateTime $date_debut = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThan(propertyPath: 'date_debut', message: 'La date de fin doit être postérieure à la date de début.')]
    private ?\DateTime $date_fin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le statut ne peut pas être vide.')]
    #[Assert\Choice(choices: ['En Attente', 'En cours', 'Terminé'], message: 'Le statut doit être En Attente, En cours ou Terminé.')]
    private ?string $statut = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le type ne peut pas être vide.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le type doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le type ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'tournois')]
    #[Assert\NotNull(message: 'Le jeu doit être sélectionné.')]
    private ?Jeu $jeu = null;

    #[ORM\ManyToMany(targetEntity: Equipe::class, mappedBy: 'Tournois')]
    private Collection $equipes;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 1000, notInRangeMessage: 'Le nombre de participants doit être entre {{ min }} et {{ max }}.')]
    private ?int $maxParticipants = null;

    // cagnotte stockée en tant que decimal (chaîne) pour éviter les
    // imprécisions du type flottant. On utilise precision/scale 10,2 par
    // exemple (max 99999999.99). Lors de l'utilisation, faire la
    // conversion appropriée ou utiliser une librairie Money.
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'La cagnotte ne peut pas être négative.')]
    private ?string $cagnotte = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $dateInscriptionLimite = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Les frais d\'inscription ne peuvent pas être négatifs.')]
    private ?float $fraisInscription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $description = null;

    /**
     * @var Collection<int, MatchGame>
     */
    #[ORM\OneToMany(targetEntity: MatchGame::class, mappedBy: 'tournoi')]
    private Collection $matchGames;

    public function __construct()
    {
        $this->equipes = new ArrayCollection();
        $this->matchGames = new ArrayCollection();
    }

    /**
     * Remplit dates de début / fin et limite d'inscription pour un nouveau tournoi (formulaire admin ou public).
     */
    public function applyDefaultSchedule(): static
    {
        $debut = new \DateTime('tomorrow');
        $debut->setTime(10, 0, 0);
        $fin = (clone $debut)->modify('+7 days');
        $fin->setTime(18, 0, 0);
        $this->setDateDebut($debut);
        $this->setDateFin($fin);
        $inscription = (clone $debut)->modify('-1 day');
        $inscription->setTime(23, 59, 59);
        $this->setDateInscriptionLimite($inscription);

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(?\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getJeu(): ?Jeu
    {
        return $this->jeu;
    }

    public function setJeu(?Jeu $jeu): static
    {
        $this->jeu = $jeu;

        return $this;
    }

    public function getEquipes(): Collection
    {
        return $this->equipes;
    }

    public function addEquipe(Equipe $equipe): static
    {
        if (!$this->equipes->contains($equipe)) {
            $this->equipes->add($equipe);
            $equipe->addTournoi($this);
        }

        return $this;
    }

    public function removeEquipe(Equipe $equipe): static
    {
        if ($this->equipes->removeElement($equipe)) {
            $equipe->removeTournoi($this);
        }

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    /**
     * Retourne la cagnotte sous forme de chaîne décimale (ex. "1234.56").
     */
    public function getCagnotte(): ?string
    {
        return $this->cagnotte;
    }

    /**
     * Définit la cagnotte; attend une chaîne correspondant à un nombre
     * décimal ou null. Par exemple "100.00". Utiliser des helpers
     * / Money lib pour manipuler cette valeur.
     */
    public function setCagnotte(?string $cagnotte): static
    {
        $this->cagnotte = $cagnotte;

        return $this;
    }

    public function getDateInscriptionLimite(): ?\DateTime
    {
        return $this->dateInscriptionLimite;
    }

    public function setDateInscriptionLimite(?\DateTime $dateInscriptionLimite): static
    {
        $this->dateInscriptionLimite = $dateInscriptionLimite;

        return $this;
    }

    public function getFraisInscription(): ?float
    {
        return $this->fraisInscription;
    }

    public function setFraisInscription(?float $fraisInscription): static
    {
        $this->fraisInscription = $fraisInscription;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, MatchGame>
     */
    public function getMatchGames(): Collection
    {
        return $this->matchGames;
    }

    public function addMatchGame(MatchGame $matchGame): static
    {
        if (!$this->matchGames->contains($matchGame)) {
            $this->matchGames->add($matchGame);
            $matchGame->setTournoi($this);
        }

        return $this;
    }

    public function removeMatchGame(MatchGame $matchGame): static
    {
        if ($this->matchGames->removeElement($matchGame)) {
            if ($matchGame->getTournoi() === $this) {
                $matchGame->setTournoi(null);
            }
        }

        return $this;
    }
}
