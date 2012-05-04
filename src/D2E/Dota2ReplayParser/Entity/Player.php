<?php

namespace D2E\Dota2ReplayParser\Entity;

class Player
{
    /**
     * Name of player
     *
     * @var string
     */
    private $name;

    /**
     * Player hero
     *
     * @var string
     */
    private $hero;

    /**
     * Player id
     *
     * @var integer
     */
    private $id;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getHero()
    {
        return $this->hero;
    }

    public function setHero($hero)
    {
        $this->hero = $hero;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}
