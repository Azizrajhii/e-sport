<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom de l'équipe ne peut pas être vide.")]
    #[Assert\Length(max: 100, maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères.")]
    private string $nom = '';

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[Assert\Count(max: 5, maxMessage: "Une équipe ne peut pas avoir plus de {{ limit }} membres.")]
    /** @var Collection<int, User> */
    private Collection $members;

    #[ORM\ManyToMany(targetEntity: Tournoi::class, inversedBy: 'equipes')]
    /** @var Collection<int, Tournoi> */
    private Collection $Tournois;
    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 100, notInRangeMessage: "Le nombre maximum de membres doit être entre {{ min }} et {{ max }}.")]
    private int $maxMembers = 5;


    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Une équipe doit avoir un propriétaire.")]
    private ?User $owner = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->Tournois = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

  public function getNom(): string
{
    return $this->nom;
}

public function setNom(string $nom): self
{
    $this->nom = $nom;
    return $this;
}


    /**
     * @return Collection<int, Tournoi>
     */
    public function getTournois(): Collection
    {
        return $this->Tournois;
    }

    public function addTournoi(Tournoi $Tournoi): self
    {
        if (!$this->Tournois->contains($Tournoi)) {
            $this->Tournois->add($Tournoi);
        }
        return $this;
    }

    public function removeTournoi(Tournoi $Tournoi): self
    {
        $this->Tournois->removeElement($Tournoi);
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(User $user): self
{
    if ($this->members->contains($user)) {
        return $this;
    }

    if ($this->members->count() >= $this->maxMembers) {
        throw new \DomainException('Impossible d\'ajouter : l\'équipe est pleine.');
    }

    $this->members->add($user);

    return $this;
}


    public function removeMember(User $user): self
    {
        $this->members->removeElement($user);
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }
    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }
    public function getMaxMembers(): int
{
    return $this->maxMembers;
}

public function setMaxMembers(int $maxMembers): self
{
    $this->maxMembers = $maxMembers;
    return $this;
}

}

