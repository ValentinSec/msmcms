<?php
/**
 * Items Manager - MU Caníbal
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 * ARCHIVO CRÍTICO: Gestión de nombres, opciones excelentes, harmony, 380, sockets y sets ancient.
 */

class ItemsManager {
    
    public static function getItemName($group, $id) {
        $names = [
            0 => [0=>'Kris', 1=>'Short Sword', 2=>'Rapier', 3=>'Katana', 4=>'Sword of Assassin', 5=>'Blade', 6=>'Gladius', 7=>'Falchion', 8=>'Serpent Sword', 9=>'Sword of Salamander', 10=>'Light Saber', 11=>'Legendary Sword', 12=>'Heliacal Sword', 13=>'Double Blade', 14=>'Lighting Sword', 15=>'Giant Sword', 16=>'Sword of Destruction', 17=>'Dark Breaker', 18=>'Thunder Blade', 19=>'Divine Sword of Archangel', 20=>'Knight Blade', 21=>'Dark Reign Blade', 22=>'Bone Blade', 23=>'Explosion Blade', 24=>'Daybreak', 25=>'Sword Dancer', 26=>'Flameberge', 27=>'Sword Breaker', 28=>'Rune Bastard Sword', 31=>'Rune Blade', 32=>'Sacred Glove', 33=>'Holy Storm Claws', 34=>'Piercing Blade Glove', 35=>'Phoenix Soul Star'],
            1 => [0=>'Small Axe', 1=>'Hand Axe', 2=>'Double Axe', 3=>'Tomahawk', 4=>'Elven Axe', 5=>'Battle Axe', 6=>'Nikea Axe', 7=>'Larkan Axe', 8=>'Crescent Axe'],
            2 => [0=>'Mace', 1=>'Morning Star', 2=>'Flail', 3=>'Great Hammer', 4=>'Crystal Morning Star', 5=>'Crystal Sword', 6=>'Chaos Dragon Axe', 7=>'Elemental Mace', 8=>'Battle Scepter', 9=>'Master Scepter', 10=>'Great Scepter', 11=>'Lord Scepter', 12=>'Great Scepter', 13=>'Divine Scepter of Archangel', 14=>'Soleil Scepter', 15=>'Shining Scepter', 16=>'Frost Mace', 17=>'Absolute Scepter', 18=>'Striker Scepter'],
            3 => [0=>'Light Spear', 1=>'Spear', 2=>'Dragon Lance', 3=>'Giant Trident', 4=>'Serpent Spear', 5=>'Double Poleaxe', 6=>'Halberd', 7=>'Berdysh', 8=>'Great Scythe', 9=>'Bill of Balrog', 10=>'Dragon Spear', 11=>'Brova'],
            4 => [0=>'Short Bow', 1=>'Bow', 2=>'Elven Bow', 3=>'Battle Bow', 4=>'Tiger Bow', 5=>'Silver Bow', 6=>'Chaos Nature Bow', 7=>'Bolt', 8=>'Crossbow', 9=>'Golden Crossbow', 10=>'Arquebus', 11=>'Light Crossbow', 12=>'Serpent Crossbow', 13=>'Bluewing Crossbow', 14=>'Aquagold Crossbow', 15=>'Arrow', 16=>'Saint Crossbow', 17=>'Celestial Bow', 18=>'Divine Crossbow of Archangel', 19=>'Great Reign Crossbow', 20=>'Arrow Viper Bow', 21=>'Sylph Wind Bow', 22=>'Albatross Bow', 23=>'Dark Stinger', 24=>'Aileen Bow'],
            5 => [0=>'Skull Staff', 1=>'Angelic Staff', 2=>'Serpent Staff', 3=>'Thunder Staff', 4=>'Gorgon Staff', 5=>'Legendary Staff', 6=>'Staff of Resurrection', 7=>'Chaos Lighting Staff', 8=>'Staff of Destruction', 9=>'Dragon Soul Staff', 10=>'Divine Staff of Archangel', 11=>'Staff of Kundun', 12=>'Grand Viper Staff', 13=>'Platina Staff', 14=>'Mystery Stick', 15=>'Violent Wind Stick', 16=>'Red Wing Stick', 17=>'Ancient Stick', 18=>'Demonic Stick', 19=>'Storm Blitz Stick', 20=>'Eternal Wing Stick', 21=>'Book of Shamut', 22=>'Book of Neil', 23=>'Book of Lagle', 30=>'Deadly Staff', 31=>'Inberial Staff', 32=>'Summon Spirit Stick', 33=>'Chrome Staff', 34=>'Raven Stick', 36=>'Divine Stick of the Archangel'],
            6 => [0=>'Small Shield', 1=>'Horn Shield', 2=>'Kite Shield', 3=>'Elven Shield', 4=>'Buckler', 5=>'Dragon Slayer Shield', 6=>'Skull Shield', 7=>'Spiked Shield', 8=>'Tower Shield', 9=>'Plate Shield', 10=>'Large Round Shield', 11=>'Serpent Shield', 12=>'Bronze Shield', 13=>'Dragon Shield', 14=>'Legendary Shield', 15=>'Grand Soul Shield', 16=>'Elemental Shield', 17=>'Crimson Glory', 18=>'Salamander Shield', 19=>'Frost Barrier', 20=>'Guardian Shield', 21=>'Cross Shield'],
            7 => [0=>'Bronze Helm', 1=>'Dragon Helm', 2=>'Pad Helm', 3=>'Legendary Helm', 4=>'Bone Helm', 5=>'Leather Helm', 6=>'Scale Helm', 7=>'Sphinx Mask', 8=>'Brass Helm', 9=>'Plate Helm', 10=>'Vine Helm', 11=>'Silk Helm', 12=>'Wind Helm', 13=>'Spirit Helm', 14=>'Guardian Helm', 15=>'Storm Crow Helm', 16=>'Black Dragon Helm', 17=>'Dark Phoenix Helm', 18=>'Grand Soul Helm', 19=>'Holy Spirit Helm', 20=>'Thunder Hawk Helm', 21=>'Great Dragon Helm', 22=>'Dark Soul Helm', 23=>'Hurricane Helm', 24=>'Red Spirit Helm', 25=>'Light Plate Mask', 26=>'Adamantine Mask', 27=>'Dark Steel Mask', 28=>'Dark Master Mask', 29=>'Dragon Knight Helm', 30=>'Venom Mist Helm', 31=>'Sylphid Ray Helm', 32=>'Volcano Helm', 33=>'Sunlight Mask', 34=>'Ashcrow Helm', 35=>'Eclipse Helm', 36=>'Iris Helm', 37=>'Valiant Helm', 38=>'Glorious Mask', 39=>'Violent Wind Helm', 40=>'Red Wing Helm', 41=>'Ancient Helm', 42=>'Demonic Helm', 43=>'Storm Blitz Helm', 44=>'Eternal Wing Helm', 45=>'Titan  Helm', 46=>'Brave Helm', 47=>'Phantom  Helm', 48=>'Destroy Helm', 49=>'Seraphim Helm', 50=>'Divine Helm', 51=>'Royal Mask', 52=>'Hades Helm', 53=>'Succubus Helm', 59=>'Sacred Fire Helm', 60=>'Storm Jahad Helm', 61=>'Piercing Groove Helm', 73=>'Phoenix Soul Helmet'],
            8 => [0=>'Bronze Armor', 1=>'Dragon Armor', 2=>'Pad Armor', 3=>'Legendary Armor', 4=>'Bone Armor', 5=>'Leather Armor', 6=>'Scale Armor', 7=>'Sphinx Armor', 8=>'Brass Armor', 9=>'Plate Armor', 10=>'Vine Armor', 11=>'Silk Armor', 12=>'Wind Armor', 13=>'Spirit Armor', 14=>'Guardian Armor', 15=>'Storm Crow Armor', 16=>'Black Dragon Armor', 17=>'Dark Phoenix Armor', 18=>'Grand Soul Armor', 19=>'Holy Spirit Armor', 20=>'Thunder Hawk Armor', 21=>'Great Dragon Armor', 22=>'Dark Soul Armor', 23=>'Hurricane Armor', 24=>'Red Spirit Armor', 25=>'Light Plate Armor', 26=>'Adamantine Armor', 27=>'Dark Steel Armor', 28=>'Dark Master Armor', 29=>'Dragon Knight Armor', 30=>'Venom Mist Armor', 31=>'Sylphid Ray Armor', 32=>'Volcano Armor', 33=>'Sunlight Armor', 34=>'Ashcrow Armor', 35=>'Eclipse Armor', 36=>'Iris Armor', 37=>'Valiant Armor', 38=>'Glorious Armor', 39=>'Violent Wind Armor', 40=>'Red Wing Armor', 41=>'Ancient Armor', 42=>'Demonic Armor', 43=>'Storm Blitz Armor', 44=>'Eternal Wing Armor', 45=>'Titan  Armor', 46=>'Brave Armor', 47=>'Phantom  Armor', 48=>'Destroy Armor', 49=>'Seraphim Armor', 50=>'Divine Armor', 51=>'Royal Armor', 52=>'Hades Armor', 53=>'Succubus Armor', 59=>'Sacred Fire Armor', 60=>'Storm Jahad Armor', 61=>'Piercing Groove Armor', 73=>'Phoenix Soul Armor'],
            9 => [0=>'Bronze Pants', 1=>'Dragon Pants', 2=>'Pad Pants', 3=>'Legendary Pants', 4=>'Bone Pants', 5=>'Leather Pants', 6=>'Scale Pants', 7=>'Sphinx Pants', 8=>'Brass Pants', 9=>'Plate Pants', 10=>'Vine Pants', 11=>'Silk Pants', 12=>'Wind Pants', 13=>'Spirit Pants', 14=>'Guardian Pants', 15=>'Storm Crow Pants', 16=>'Black Dragon Pants', 17=>'Dark Phoenix Pants', 18=>'Grand Soul Pants', 19=>'Holy Spirit Pants', 20=>'Thunder Hawk Pants', 21=>'Great Dragon Pants', 22=>'Dark Soul Pants', 23=>'Hurricane Pants', 24=>'Red Spirit Pants', 25=>'Light Plate Pants', 26=>'Adamantine Pants', 27=>'Dark Steel Pants', 28=>'Dark Master Pants', 29=>'Dragon Knight Pants', 30=>'Venom Mist Pants', 31=>'Sylphid Ray Pants', 32=>'Volcano Pants', 33=>'Sunlight Pants', 34=>'Ashcrow Pants', 35=>'Eclipse Pants', 36=>'Iris Pants', 37=>'Valiant Pants', 38=>'Glorious Pants', 39=>'Violent Wind Pants', 40=>'Red Wing Pants', 41=>'Ancient Pants', 42=>'Demonic Pants', 43=>'Storm Blitz Pants', 44=>'Eternal Wing Pants', 45=>'Titan  Pants', 46=>'Brave Pants', 47=>'Phantom  Pants', 48=>'Destroy Pants', 49=>'Seraphim Pants', 50=>'Divine Pants', 51=>'Royal Pants', 52=>'Hades Pants', 53=>'Succubus Pants', 59=>'Sacred Fire Pants', 60=>'Storm Jahad Pants', 61=>'Piercing Groove Pants', 73=>'Phoenix Soul Pants'],
            10 => [0=>'Bronze Gloves', 1=>'Dragon Gloves', 2=>'Pad Gloves', 3=>'Legendary Gloves', 4=>'Bone Gloves', 5=>'Leather Gloves', 6=>'Scale Gloves', 7=>'Sphinx Gloves', 8=>'Brass Gloves', 9=>'Plate Gloves', 10=>'Vine Gloves', 11=>'Silk Gloves', 12=>'Wind Gloves', 13=>'Spirit Gloves', 14=>'Guardian Gloves', 15=>'Storm Crow Gloves', 16=>'Black Dragon Gloves', 17=>'Dark Phoenix Gloves', 18=>'Grand Soul Gloves', 19=>'Holy Spirit Gloves', 20=>'Thunder Hawk Gloves', 21=>'Great Dragon Gloves', 22=>'Dark Soul Gloves', 23=>'Hurricane Gloves', 24=>'Red Spirit Gloves', 25=>'Light Plate Gloves', 26=>'Adamantine Gloves', 27=>'Dark Steel Gloves', 28=>'Dark Master Gloves', 29=>'Dragon Knight Gloves', 30=>'Venom Mist Gloves', 31=>'Sylphid Ray Gloves', 32=>'Volcano Gloves', 33=>'Sunlight Gloves', 34=>'Ashcrow Gloves', 35=>'Eclipse Gloves', 36=>'Iris Gloves', 37=>'Valiant Gloves', 38=>'Glorious Gloves', 39=>'Violent Wind Gloves', 40=>'Red Wing Gloves', 41=>'Ancient Gloves', 42=>'Demonic Gloves', 43=>'Storm Blitz Gloves', 44=>'Eternal Wing Gloves', 45=>'Titan  Gloves', 46=>'Brave Gloves', 47=>'Phantom  Gloves', 48=>'Destroy Gloves', 49=>'Seraphim Gloves', 50=>'Divine Gloves', 51=>'Royal Gloves', 52=>'Hades Gloves', 53=>'Succubus Gloves'],
            11 => [0=>'Bronze Boots', 1=>'Dragon Boots', 2=>'Pad Boots', 3=>'Legendary Boots', 4=>'Bone Boots', 5=>'Leather Boots', 6=>'Scale Boots', 7=>'Sphinx Boots', 8=>'Brass Boots', 9=>'Plate Boots', 10=>'Vine Boots', 11=>'Silk Boots', 12=>'Wind Boots', 13=>'Spirit Boots', 14=>'Guardian Boots', 15=>'Storm Crow Boots', 16=>'Black Dragon Boots', 17=>'Dark Phoenix Boots', 18=>'Grand Soul Boots', 19=>'Holy Spirit Boots', 20=>'Thunder Hawk Boots', 21=>'Great Dragon Boots', 22=>'Dark Soul Boots', 23=>'Hurricane Boots', 24=>'Red Spirit Boots', 25=>'Light Plate Boots', 26=>'Adamantine Boots', 27=>'Dark Steel Boots', 28=>'Dark Master Boots', 29=>'Dragon Knight Boots', 30=>'Venom Mist Boots', 31=>'Sylphid Ray Boots', 32=>'Volcano Boots', 33=>'Sunlight Boots', 34=>'Ashcrow Boots', 35=>'Eclipse Boots', 36=>'Iris Boots', 37=>'Valiant Boots', 38=>'Glorious Boots', 39=>'Violent Wind Boots', 40=>'Red Wing Boots', 41=>'Ancient Boots', 42=>'Demonic Boots', 43=>'Storm Blitz Boots', 44=>'Eternal Wing Boots', 45=>'Titan Boots', 46=>'Brave Boots', 47=>'Phantom Boots', 48=>'Destroy Boots', 49=>'Seraphim Boots', 50=>'Divine Boots', 51=>'Royal Boots', 52=>'Hades Boots', 53=>'Succubus Boots', 59=>'Sacred Fire Boots', 60=>'Storm Jahad Boots', 61=>'Piercing Groove Boots', 73=>'Phoenix Soul Boots'],
            12 => [0=>'Wings of Elf', 1=>'Wings of Heaven', 2=>'Wings of Satan', 3=>'Wings of Spirit', 4=>'Wings of Soul', 5=>'Wings of Dragon', 6=>'Wings of Darkness', 36=>'Wings of Storm', 37=>'Wings of Eternal', 38=>'Wings of Illusion', 39=>'Wings of Ruin', 40=>'Cape of Emperor', 41=>'Wings of Curse', 42=>'Wings of Despair', 43=>'Wings of Dimension', 49=>'Cape of Fighter', 50=>'Cape of Overrule', 130=>'Small Cape of Lord', 131=>'Small Wing of Curse', 132=>'Small Wings of Elf', 133=>'Small Wings of Heaven', 134=>'Small Wings of Satan', 135=>'Little Warrior\'s Cloak'],
            13 => [0=>'Guardian Angel', 1=>'Imp', 2=>'Horn of Uniria', 3=>'Horn of Dinorant', 4=>'Dark Horse', 5=>'Dark Raven', 8=>'Ring of Ice', 9=>'Ring of Poison', 10=>'Transformation Ring', 12=>'Pendant of Lighting', 13=>'Pendant of Fire', 20=>'Wizards Ring', 21=>'Ring of Fire', 22=>'Ring of Earth', 23=>'Ring of Wind', 24=>'Ring of Magic', 25=>'Pendant of Ice', 26=>'Pendant of Wind', 27=>'Pendant of Water', 28=>'Pendant of Ability', 29=>'Armor of Guardsman', 30=>'Cape of Lord', 37=>'Horn of Fenrir', 38=>'Moonstone Pendant', 39=>'EliteTransfer Skeleton Ring', 40=>'Jack Olantern Ring', 41=>'Transfer Christmas Ring', 42=>'Ring of GM', 64=>'Demon', 65=>'Spirit of Guardian', 67=>'Pet Rudolph', 68=>'Snowman Transformation Ring', 76=>'Panda Ring', 80=>'Pet Panda', 106=>'Pet Unicorn', 107=>'Lethal Wizard\'s Ring', 109=>'Sapphire Ring', 110=>'Ruby Ring', 111=>'Topaz Ring', 112=>'Amethyst Ring', 113=>'Ruby Necklace', 114=>'Emerald Necklace', 115=>'Sapphire Necklace', 122=>'Skeleton Transformation Ring', 123=>'Pet Skeleton'],
            14 => [0=>'Apple', 1=>'Small Healing Potion', 2=>'Healing Potion', 3=>'Large Healing Potion', 4=>'Small Mana Potion', 5=>'Mana Potion', 6=>'Large Mana Potion', 7=>'Siege Potion', 8=>'Antidote', 9=>'Ale', 10=>'Town Portal Scroll', 11=>'Box of Luck', 12=>'Heart', 13=>'Jewel of Bless', 14=>'Jewel of Soul', 15=>'Zen', 16=>'Jewel of Life', 17=>'Devil\'s Eye', 18=>'Devil\'s Key', 19=>'Devil\'s Invitation', 20=>'Remedy of Love', 21=>'Rena', 22=>'Jewel of Creation', 23=>'Scroll of the Emperor', 24=>'Broken Sword', 25=>'Tear of Elf', 26=>'Soul Shard of Wizard', 28=>'Lost Map', 29=>'Symbol of Kundun', 31=>'Jewel of Guardian', 32=>'Pink Chocolate Box', 33=>'Red Chocolate Box', 34=>'Blue Chocolate Box', 35=>'Small SD Potion', 36=>'SD Potion', 37=>'Large SD Potion', 38=>'Small Complex Potion', 39=>'Complex Potion', 40=>'Large Complex Potion', 41=>'Gemstone', 42=>'Jewel of Harmony', 43=>'Lower refining stone', 44=>'Higher refining stone', 45=>'Pumpkin of Luck', 46=>'Jack O\'Lantern Blessings', 47=>'Jack O\'Lantern Wrath', 48=>'Jack O\'Lantern Cry', 49=>'Jack O\'Lantern Food', 50=>'Jack O\'Lantern Drink', 51=>'Christmas Star', 52=>'GM Gift', 53=>'Talisman of Luck', 54=>'Chaos Card', 55=>'Green Chaos Box', 56=>'Red Chaos Box', 57=>'Purple Chaos Box', 58=>'Rare Item Ticket1', 59=>'Rare Item Ticket2', 60=>'Rare Item Ticket3', 61=>'Rare Item Ticket4', 62=>'Rare Item Ticket5', 63=>'Firecracker', 64=>'Cursed Castle Water', 65=>'Death-beam Knight Flame', 66=>'Hell-Miner Horn', 67=>'Dark Phoenix Feather', 68=>'Abyssal Eye', 70=>'Elite Healing Potion', 71=>'Elite Mana Potion', 72=>'Scroll of Quickness', 73=>'Scroll of Defense', 74=>'Scroll of Wrath', 75=>'Scroll of Wizardry', 76=>'Scroll of Health', 77=>'Scroll of Mana', 78=>'Elixir of Strength', 79=>'Elixir of Agility', 80=>'Elixir of Health', 81=>'Elixir of Energy', 82=>'Elixir of Control', 83=>'Rare Item Ticket 6', 84=>'Cherry Blossom Play-Box', 85=>'Cherry Blossom Wine', 86=>'Cherry Blossom Rice Cake', 87=>'Cherry Blossom Flower Petal', 88=>'White Cherry Blossom Branch', 89=>'Red Cherry Blossom Branch', 90=>'Golden Cherry Blossom Branch', 91=>'Summoner Character Card', 92=>'Chaos Card Gold', 93=>'Chaos Card Rare', 94=>'Medium Elite Healing Potion', 95=>'Chaos Card Mini', 96=>'Talisman of Chaos Assembly', 97=>'Scroll of Battle', 98=>'Scroll of Strength', 99=>'Christmas Firecracker', 100=>'Lucky Coin', 101=>'Suspicious Scrap of Paper', 102=>'Gaion\'s Order', 103=>'First Secromicon Fragment', 104=>'Second Secromicon Fragment', 105=>'Third Secromicon Fragment', 106=>'Fourth Secromicon Fragment', 107=>'Fifth Secromicon Fragment', 108=>'Sixth Secromicon Fragment', 109=>'Complete Secromicon', 110=>'Sign of Dimensions', 111=>'Mirror of Dimensions', 112=>'Silver Key', 113=>'Gold Key', 114=>'Master Skill Reset', 115=>'Stat Adjustment', 116=>'Character Relocation Service', 117=>'Character Rename Service', 118=>'Server Relocation Service', 119=>'Premium Package', 120=>'Goblin Gold Coin', 121=>'Sealed Golden Box', 122=>'Sealed Silver Box', 123=>'Golden Box', 124=>'Silver Box', 125=>'Package Box', 126=>'30-Day Pass', 127=>'90-Day Pass', 128=>'30-Day Pass - Points', 129=>'90-Day Pass - Points', 130=>'3-Hour Pass', 131=>'5-Hour Pass', 132=>'10-Hour Pass', 133=>'Elite SD Potion', 134=>'Package Box A', 135=>'Package Box B', 136=>'Package Box C', 137=>'Package Box D', 138=>'Package Box E', 139=>'Package Box F', 140=>'Scroll of Healing', 141=>'Shining Jewellery Case', 142=>'Elegant Jewellery Case', 143=>'Steel Jewellery Case', 144=>'Old Jewellery Case', 145=>'Rare Item Ticket 7', 146=>'Rare Item Ticket 8', 147=>'Rare Item Ticket 9', 148=>'Rare Item Ticket 10', 149=>'Rare Item Ticket 11', 150=>'Rare Item Ticket 12', 151=>'Lv.1 Letter of Request', 152=>'Lv.1 Completion Certificate', 153=>'Stardust', 154=>'Kalt Stone', 155=>'Tantalos\'s Armor', 156=>'Burnt Murderer\'s Club', 157=>'Green Box', 158=>'Red Box', 159=>'Purple Box', 160=>'Jewel of Extension', 161=>'Jewel of Elevation', 162=>'Magic Backpack', 163=>'Vault Expansion Certificate', 164=>'Ancient Statue', 165=>'Magic Cloth', 166=>'Space Cloth', 167=>'Phase Cloth', 168=>'Dimension Cloth', 169=>'RageFighter Character Card'],
            15 => [0=>'Scroll of Poison', 1=>'Scroll of Meteorite', 2=>'Scroll of Lighting', 3=>'Scroll of Fire Ball', 4=>'Scroll of Flame', 5=>'Scroll of Teleport', 6=>'Scroll of Ice', 7=>'Scroll of Twister', 8=>'Scroll of Evil Spirit', 9=>'Scroll of Hellfire', 10=>'Scroll of Power Wave', 11=>'Scroll of Aqua Beam', 12=>'Scroll of Cometfall', 13=>'Scroll of Inferno', 14=>'Scroll of Teleport Ally', 15=>'Scroll of Soul Barrier', 16=>'Scroll of Decay', 17=>'Scroll of Ice Storm', 18=>'Scroll of Nova', 19=>'Chain Lightning Parchment', 20=>'Drain Life Parchment', 21=>'Lightning Shock Parchment', 22=>'Damage Reflection Parchment', 23=>'Berserker Parchment', 24=>'Sleep Parchment', 26=>'Weakness Parchment', 27=>'Innovation Parchment', 28=>'Scroll of Wizardry Enhance', 29=>'Scroll of Gigantic Storm', 30=>'Chain Drive Parchment', 31=>'Dark Side Parchment', 32=>'Dragon Roar Parchment', 33=>'Dragon Slasher Parchment', 34=>'Ignore Defense Parchment', 35=>'Increase Health Parchment', 36=>'Increase Block Parchment']
        ];
        return isset($names[$group][$id]) ? $names[$group][$id] : "Unknown Item";
    }

    public static function getExcellentOptions($byte7, $group, $id) {
        $exeOpts = [];
        $exeType = 'DEFENSE'; 
        if ($group <= 5 || ($group == 13 && in_array($id, [12, 13, 24, 25, 26, 27, 28]))) $exeType = 'ATTACK';
        if ($group == 12 && $id >= 36) $exeType = 'WINGS';

        $bits = [32, 16, 8, 4, 2, 1]; 
        foreach ($bits as $bit) {
            if ($byte7 & $bit) {
                if ($exeType == 'ATTACK') {
                    if($bit==32) $exeOpts[] = "Excellent Damage Rate +10%";
                    if($bit==16) $exeOpts[] = "Increase Damage +level/20";
                    if($bit==8)  $exeOpts[] = "Increase Damage +2%";
                    if($bit==4)  $exeOpts[] = "Attack Speed +7";
                    if($bit==2)  $exeOpts[] = "Increase Life after monster +life/8";
                    if($bit==1)  $exeOpts[] = "Increase Mana after monster +mana/8";
                } elseif ($exeType == 'DEFENSE') {
                    if($bit==32) $exeOpts[] = "Increase Max HP +4%";
                    if($bit==16) $exeOpts[] = "Increase Max Mana +4%";
                    if($bit==8)  $exeOpts[] = "Damage Decrease +4%";
                    if($bit==4)  $exeOpts[] = "Reflect Damage +5%";
                    if($bit==2)  $exeOpts[] = "Defense Success Rate +10%";
                    if($bit==1)  $exeOpts[] = "Increases acquisition rate of Zen after hunting monsters 30%";
                } elseif ($exeType == 'WINGS') {
                    if($bit==1)  $exeOpts[] = "Ignore opponent's defensive power by 5%";
                    if($bit==2)  $exeOpts[] = "Return's the enemy's attack power by 5%";
                    if($bit==4)  $exeOpts[] = "Complete recovery of HP in 5% rate";
                    if($bit==8)  $exeOpts[] = "Complete recovery of Mana in 5% rate";
                }
            }
        }
        return $exeOpts;
    }

    public static function getHarmonyOption($hType, $byte10) {
        $harmonyMap = [
            1 => [ // Attack
                1 => "Min. attack increase",
                2 => "Max. attack increase",
                3 => "Required Strength decrease",
                4 => "Required Dexterity decrease",
                5 => "Attack damage increase",
                6 => "Critical damage increase",
                7 => "Skill attack increase",
                8 => "Attack success rate increase",
                9 => "SD decrease rate increase",
                10 => "SD bypass rate increase",
            ],
            5 => [ // Wizardry
                1 => "Wizardry increase",
                2 => "Required Strength decrease",
                3 => "Required Dexterity decrease",
                4 => "Skill attack increase",
                5 => "Critical damage increase",
                6 => "SD decrease rate increase",
                7 => "Attack success rate increase",
                8 => "SD bypass rate increase",
            ],
            6 => [ // Defense
                1 => "Defense increase",
                2 => "Max. AG increase",
                3 => "Max. HP increase",
                4 => "HP recovery increase",
                5 => "Mana recovery increase",
                6 => "Defense success rate increase",
                7 => "Damage reduction increase",
                8 => "SD ratio increase",
            ]
        ];

        $optId = ($byte10 >> 4);
        $optLvl = ($byte10 & 0x0F);

        if (isset($harmonyMap[$hType][$optId])) {
            return $harmonyMap[$hType][$optId] . " (+$optLvl)";
        }
        
        return "";
    }

    public static $OPTION_380_NAMES = [
        1 => "Attack Success Rate Increase +10",
        2 => "Additional Damage +200",
        3 => "Defense Success Rate Increase (PVP) +10",
        5 => "Max. HP Increase +200",
        6 => "Max. SD Increase +700",
        7 => "SD Auto Recovery",
        8 => "SD Recovery Rate Increase +20%",
        9 => "Defensive Skill +200",
    ];

    public static $ITEM_380_CONFIG = [
        '0-22' => [2, 1], '0-23' => [2, 1], '0-24' => [2, 1], '0-25' => [2, 1], 
        '2-14' => [2, 1], '4-21' => [2, 1], '5-12' => [2, 1], '5-19' => [2, 1],
        '7-29' => [8, 3], '7-30' => [8, 3], '7-31' => [8, 3], '7-32' => [8, 3], '7-33' => [8, 3],
        '8-29' => [7, 3], '8-30' => [7, 3], '8-31' => [7, 3], '8-32' => [7, 3], '8-33' => [7, 3],
        '9-29' => [9, 3], '9-30' => [9, 3], '9-31' => [9, 3], '9-32' => [9, 3], '9-33' => [9, 3],
        '10-29' => [5, 3], '10-30' => [5, 3], '10-31' => [5, 3], '10-32' => [5, 3], '10-33' => [5, 3],
        '11-29' => [6, 3], '11-30' => [6, 3], '11-31' => [6, 3], '11-32' => [6, 3], '11-33' => [6, 3],
    ];
    
    public static function get380Options($group, $id) {
        $key = $group . "-" . $id;
        $results = [];
        if (isset(self::$ITEM_380_CONFIG[$key])) {
            foreach (self::$ITEM_380_CONFIG[$key] as $optIdx) {
                if (isset(self::$OPTION_380_NAMES[$optIdx])) {
                    $results[] = self::$OPTION_380_NAMES[$optIdx];
                }
            }
        }
        return $results;
    }
    
    public static function getSocketOption($value) {
        if ($value == 254) return "Socket: No item application";
        if ($value == 255 || $value == 0) return ""; 

        $optionId = $value % 50;
        $sockets = [
            0 => "Fire (Attack/Wizardry increase)", 
            1 => "Fire (Attack Speed increase)", 
            2 => "Fire (Max Attack/Wizardry increase)", 
            3 => "Fire (Min Attack/Wizardry increase)", 
            4 => "Fire (Attack/Wizardry increase)", 
            5 => "Fire (AG consumption decrease)", 
            10 => "Water (Block Rating increase)", 
            11 => "Water (Defense increase)", 
            12 => "Water (Shield Protection increase)", 
            13 => "Water (Damage Reduction)", 
            14 => "Water (Damage Reflection)", 
            16 => "Ice (Monster Die Life recovery)", 
            17 => "Ice (Monster Die Mana recovery)", 
            18 => "Ice (Skill attack power increase)", 
            19 => "Ice (Attack Accuracy increase)", 
            20 => "Ice (Item durability increase)", 
            21 => "Wind (Auto Life recovery increase)", 
            22 => "Wind (Max Life increase)", 
            23 => "Wind (Max Mana increase)", 
            24 => "Wind (Auto Mana recovery increase)", 
            25 => "Wind (Max AG increase)", 
            26 => "Wind (AG value increase)", 
            29 => "Lightning (Excellent Damage increase)", 
            30 => "Lightning (Excellent Damage Rate increase)", 
            31 => "Lightning (Critical Damage increase)", 
            32 => "Lightning (Critical Damage Rate increase)", 
            34 => "Earth (Strength increase)", 
            35 => "Earth (Dexterity increase)", 
            36 => "Earth (Health increase)", 
            37 => "Earth (Energy increase)"
        ];

        if (isset($sockets[$optionId])) {
            $seedLevel = floor($value / 50) + 1;
            return "Socket: " . $sockets[$optionId] . " (Lvl " . $seedLevel . ")";
        }
        return "Socket: Unknown Seed (" . $value . ")";
    }

    public static $ANCIENT_SETS = [
        1 => ['name' => "Warrior", 'opts' => [[0, '10'], [19, '10'], [13, '20'], [14, '5'], [10, '20'], [1, '10']], 'full_opts' => [[15, '5'], [17, '5'], [0, '25']]],
        2 => ['name' => "Anonymous", 'opts' => [[11, '50'], [1, '50'], [22, '25']], 'full_opts' => [[8, '30']]],
        3 => ['name' => "Hyperion", 'opts' => [[2, '15'], [1, '15']], 'full_opts' => [[19, '20'], [12, '30']]],
        4 => ['name' => "Mist", 'opts' => [[3, '20'], [19, '30']], 'full_opts' => [[20, '10'], [1, '20']]],
        5 => ['name' => "Eplete", 'opts' => [[19, '15'], [9, '50'], [7, '5'], [11, '50']], 'full_opts' => [[13, '30'], [15, '10'], [17, '10']]],
        6 => ['name' => "Berserker", 'opts' => [[6, '10'], [6, '20'], [6, '30'], [6, '40']], 'full_opts' => [[19, '40'], [0, '40']]],
        7 => ['name' => "Garuda", 'opts' => [[13, '30'], [20, '5'], [2, '15'], [11, '50']], 'full_opts' => [[19, '25'], [7, '15']]],
        8 => ['name' => "Cloud", 'opts' => [[15, '20']], 'full_opts' => [[16, '50']]],
        9 => ['name' => "Kantata", 'opts' => [[2, '15'], [3, '30'], [7, '10'], [0, '15']], 'full_opts' => [[19, '25'], [17, '10'], [18, '20']]],
        10 => ['name' => "Rave", 'opts' => [[19, '20'], [20, '10']], 'full_opts' => [[23, '30'], [21, '5']]],
        11 => ['name' => "Hyon", 'opts' => [[10, '25'], [20, '10'], [19, '20']], 'full_opts' => [[15, '15'], [17, '15'], [16, '20'], [18, '20']]],
        12 => ['name' => "Vicious", 'opts' => [[19, '15'], [8, '15'], [20, '10']], 'full_opts' => [[5, '20'], [6, '30'], [21, '5']]],
        13 => ['name' => "Apollo", 'opts' => [[2, '10'], [7, '5'], [19, '10'], [12, '30'], [11, '30'], [13, '20']], 'full_opts' => [[15, '10'], [17, '10'], [2, '30']]],
        14 => ['name' => "Barnake", 'opts' => [[7, '10'], [2, '20']], 'full_opts' => [[19, '30'], [12, '100']]],
        15 => ['name' => "Evis", 'opts' => [[19, '15'], [3, '20'], [7, '10']], 'full_opts' => [[20, '5'], [9, '50'], [14, '5']]],
        16 => ['name' => "Sylion", 'opts' => [[20, '5'], [15, '5'], [10, '20']], 'full_opts' => [[0, '50'], [1, '50'], [3, '50'], [2, '50']]],
        17 => ['name' => "Heras", 'opts' => [[0, '15'], [7, '10'], [22, '5'], [2, '15'], [9, '50']], 'full_opts' => [[15, '10'], [17, '10'], [11, '50'], [12, '50']]],
        18 => ['name' => "Minet", 'opts' => [[2, '30'], [10, '30']], 'full_opts' => [[12, '100'], [19, '15']]],
        19 => ['name' => "Anubis", 'opts' => [[20, '10'], [12, '50'], [7, '10']], 'full_opts' => [[15, '15'], [17, '15'], [16, '20'], [18, '20']]],
        20 => ['name' => "Isis", 'opts' => [[19, '10'], [20, '10'], [2, '30']], 'full_opts' => [[7, '10'], [21, '5']]],
        21 => ['name' => "Ceto", 'opts' => [[1, '10'], [11, '50'], [10, '20'], [22, '5'], [2, '10']], 'full_opts' => [[11, '50'], [0, '20']]],
        22 => ['name' => "Drake", 'opts' => [[1, '20'], [8, '25'], [20, '20']], 'full_opts' => [[10, '40'], [15, '10']]],
        23 => ['name' => "Gaia", 'opts' => [[19, '10'], [12, '25'], [0, '10'], [20, '5']], 'full_opts' => [[1, '30'], [17, '10'], [18, '10']]],
        24 => ['name' => "Fase", 'opts' => [[11, '100'], [12, '100']], 'full_opts' => [[10, '100']]],
        25 => ['name' => "Odin", 'opts' => [[2, '15'], [11, '50'], [9, '50'], [1, '30']], 'full_opts' => [[12, '50'], [21, '5'], [13, '50']]],
        26 => ['name' => "Elvian", 'opts' => [[1, '30']], 'full_opts' => [[21, '5']]],
        27 => ['name' => "Argo", 'opts' => [[6, '20'], [19, '25']], 'full_opts' => [[13, '50'], [20, '5']]],
        28 => ['name' => "Karis", 'opts' => [[19, '15'], [20, '10']], 'full_opts' => [[15, '10'], [1, '40']]],
        29 => ['name' => "Gywen", 'opts' => [[1, '30'], [5, '20'], [10, '20'], [6, '20']], 'full_opts' => [[15, '15'], [17, '15'], [16, '20'], [18, '20']]],
        30 => ['name' => "Aruan", 'opts' => [[8, '10'], [20, '10'], [19, '20']], 'full_opts' => [[15, '15'], [17, '15'], [21, '5']]],
        31 => ['name' => "Gaion", 'opts' => [[21, '5'], [20, '15'], [19, '15']], 'full_opts' => [[17, '15'], [18, '30'], [7, '10'], [0, '30']]],
        32 => ['name' => "Muren", 'opts' => [[19, '10'], [7, '10'], [20, '10']], 'full_opts' => [[15, '15'], [17, '15'], [10, '25'], [23, '20']]],
        33 => ['name' => "Agnis", 'opts' => [[20, '10'], [10, '40'], [19, '20']], 'full_opts' => [[15, '15'], [17, '15'], [16, '20'], [18, '20']]],
        34 => ['name' => "Broy", 'opts' => [[8, '20'], [19, '20'], [2, '30']], 'full_opts' => [[15, '15'], [17, '15'], [21, '5'], [4, '30']]],
        35 => ['name' => "Chrono", 'opts' => [[20, '20'], [10, '60'], [19, '30']], 'full_opts' => [[15, '15'], [17, '15'], [16, '20'], [18, '20']]],
        36 => ['name' => "Sedemen", 'opts' => [[7, '15'], [19, '25'], [2, '30']], 'full_opts' => [[15, '15'], [17, '15'], [21, '5']]],
        37 => ['name' => "Vega", 'opts' => [[9, '50'], [3, '50'], [6, '30']], 'full_opts' => [[17, '15'], [20, '5'], [21, '5']]],
        38 => ['name' => "Chamer", 'opts' => [[10, '50'], [20, '5'], [8, '30']], 'full_opts' => [[16, '30'], [17, '15'], [19, '30'], [18, '20']]],
        39 => ['name' => "Moros", 'opts' => [[11, '30'], [19, '25'], [10, '20'], [20, '10']], 'full_opts' => [[15, '10'], [17, '10'], [16, '10'], [18, '10']]],
        40 => ['name' => "Dione", 'opts' => [[11, '30'], [19, '25'], [10, '20'], [20, '10']], 'full_opts' => [[15, '10'], [17, '10'], [16, '10'], [18, '10']]],
        41 => ['name' => "Hades", 'opts' => [[11, '30'], [19, '25'], [10, '20'], [20, '10']], 'full_opts' => [[15, '10'], [17, '10'], [16, '10'], [18, '10']]],
        42 => ['name' => "Ophion", 'opts' => [[11, '30'], [19, '25'], [10, '20'], [20, '10']], 'full_opts' => [[15, '10'], [17, '10'], [16, '10'], [18, '10']]],
        43 => ['name' => "Meter", 'opts' => [[11, '50'], [10, '40'], [20, '15'], [15, '15']], 'full_opts' => [[17, '15'], [16, '20'], [18, '20'], [21, '5']]],
        44 => ['name' => "Hegaton", 'opts' => [[11, '50'], [10, '40'], [20, '15'], [15, '15']], 'full_opts' => [[17, '15'], [16, '20'], [18, '20'], [21, '5']]],
        45 => ['name' => "Castol", 'opts' => [[11, '50'], [10, '40'], [20, '15'], [15, '15']], 'full_opts' => [[17, '15'], [16, '20'], [18, '20'], [21, '5']]],
        46 => ['name' => "Taros", 'opts' => [[11, '50'], [10, '40'], [20, '15'], [15, '15']], 'full_opts' => [[17, '15'], [16, '20'], [18, '20'], [21, '5']]],
        47 => ['name' => "Nemesis", 'opts' => [[11, '50'], [10, '40'], [20, '15'], [15, '15']], 'full_opts' => [[17, '15'], [16, '20'], [18, '20'], [21, '5']]],
        48 => ['name' => "Amis", 'opts' => [[11, '50'], [10, '40'], [20, '15']], 'full_opts' => [[15, '15'], [17, '15'], [16, '20'], [18, '20'], [21, '5']]],
        49 => ['name' => "Trite", 'opts' => [[11, '50'], [10, '40'], [20, '15']], 'full_opts' => [[15, '15'], [17, '15'], [16, '20'], [18, '20'], [21, '5']]],
    ];

    public static $ITEM_SETS = [
        '0-2'   => [21, 0], '0-14'  => [11, 0], '0-32'  => [37, 38], '2-1'   => [1, 0], '4-5'   => [29, 0], '4-9'   => [23, 0], '5-0'   => [13, 0],
        '6-0'   => [2, 0], '6-6'   => [17, 0], '6-9'   => [5, 0],
        '7-0'   => [4, 0], '7-1'   => [11, 12], '7-2'   => [13, 14], '7-3'   => [19, 20], '7-4'   => [16, 0], '7-5'   => [1, 2], '7-6'   => [5, 6], '7-7'   => [17, 0], '7-8'   => [8, 0], '7-9'   => [10, 0], '7-10'  => [21, 22], '7-11'  => [23, 0], '7-12'  => [25, 0], '7-13'  => [28, 0], '7-14'  => [0, 30], '7-26'  => [33, 0], '7-40'  => [35, 36], '7-59'  => [37, 0],
        '8-0'   => [3, 0], '8-1'   => [0, 12], '8-2'   => [13, 0], '8-3'   => [19, 20], '8-4'   => [15, 16], '8-5'   => [1, 0], '8-6'   => [5, 6], '8-7'   => [17, 18], '8-8'   => [7, 0], '8-9'   => [9, 10], '8-10'  => [22, 0], '8-11'  => [23, 0], '8-12'  => [25, 0], '8-13'  => [27, 0], '8-14'  => [29, 30], '8-15'  => [31, 32], '8-26'  => [33, 0], '8-40'  => [0, 36], '8-59'  => [37, 38],
        '9-0'   => [3, 4], '9-1'   => [0, 12], '9-2'   => [13, 14], '9-3'   => [0, 20], '9-4'   => [15, 0], '9-5'   => [1, 2], '9-6'   => [5, 6], '9-7'   => [17, 18], '9-8'   => [7, 8], '9-9'   => [10, 0], '9-10'  => [21, 22], '9-11'  => [23, 24], '9-12'  => [25, 26], '9-13'  => [27, 28], '9-14'  => [0, 30], '9-15'  => [31, 32], '9-26'  => [33, 34], '9-40'  => [35, 0], '9-59'  => [37, 8],
        '10-0'  => [4, 0], '10-1'  => [11, 0], '10-2'  => [13, 0], '10-3'  => [19, 0], '10-4'  => [16, 0], '10-5'  => [1, 0], '10-6'  => [6, 0], '10-7'  => [17, 0], '10-8'  => [7, 0], '10-9'  => [9, 0], '10-10' => [21, 0], '10-11' => [23, 24], '10-12' => [25, 0], '10-13' => [27, 0], '10-14' => [29, 0], '10-15' => [0, 32], '10-26' => [0, 34], '10-40' => [35, 36],
        '11-0'  => [3, 0], '11-1'  => [11, 0], '11-2'  => [14, 0], '11-3'  => [0, 20], '11-4'  => [15, 16], '11-5'  => [1, 2], '11-6'  => [6, 0], '11-7'  => [17, 18], '11-8'  => [7, 0], '11-9'  => [9, 0], '11-10' => [21, 22], '11-11' => [24, 0], '11-12' => [25, 26], '11-14' => [29, 30], '11-15' => [31, 0], '11-26' => [0, 34], '11-40' => [0, 36], '11-59' => [38, 0], '11-72' => [49, 0],
        '13-8'  => [1, 0], '13-9'  => [9, 33], '13-12' => [5, 0], '13-13' => [7, 0], '13-21' => [19, 32], '13-22' => [21, 12], '13-23' => [9, 0], '13-24' => [13, 35], '13-25' => [13, 34], '13-26' => [15, 0], '13-27' => [31, 0], '13-28' => [29, 0],
    ];

    public static $OPTION_NAMES = [
        0 => "Add Strength", 1 => "Add Dexterity", 2 => "Add Energy", 3 => "Add Vitality", 4 => "Add Leadership", 5 => "Add Min Physi Damage", 6 => "Add Max Physi Damage", 7 => "Mul Magic Damage", 8 => "Add Physi Damage", 9 => "Mul Attack Sucess Rate", 10 => "Add Defense", 11 => "Add Max Life", 12 => "Add Max Mana", 13 => "Add Max BP", 14 => "Add BP Recovery", 15 => "Add Critical Damage Rate", 16 => "Add Critical Damage", 17 => "Add Excellent Damage Rate", 18 => "Add Excellent Damage", 19 => "Add Skill Damage", 20 => "Add Double Damage Rate", 21 => "Add Ignore Defense Rate", 22 => "Mul Shield Defense", 23 => "Mul Two Hand Physi Damage", 24 => "Add Physi Damage By Strength", 25 => "Add Physi Damage By Dexterity", 26 => "Add Defense By Dexterity", 27 => "Add Defense By Vitality", 28 => "Add Magic Damage By Energy", 29 => "Add Ice Damage", 30 => "Add Poison Damage", 31 => "Add Lightning Damage", 32 => "Add Fire Damage", 33 => "Add Earth Damage", 34 => "Add Wind Damage", 35 => "Add Water Damage",
    ];

    public static function getAncientName($byte8, $group, $id) {
        $key = $group . "-" . $id;
        if (!isset(self::$ITEM_SETS[$key])) return "Ancient";
        $slot = ($byte8 == 6 || $byte8 == 10 || $byte8 == 26) ? 1 : 0;
        $setIdx = self::$ITEM_SETS[$key][$slot];
        return isset(self::$ANCIENT_SETS[$setIdx]) ? self::$ANCIENT_SETS[$setIdx]['name'] : "Ancient";
    }

    public static function getAncientOptions($byte8, $group, $id) {
        $key = $group . "-" . $id;
        if (!isset(self::$ITEM_SETS[$key])) return ["Ancient Item"];
        $slot = ($byte8 == 6 || $byte8 == 10 || $byte8 == 26) ? 1 : 0;
        $setIdx = self::$ITEM_SETS[$key][$slot];
        if (!isset(self::$ANCIENT_SETS[$setIdx])) return ["Ancient Item"];
        
        $setData = self::$ANCIENT_SETS[$setIdx];
        $lines = [];
        $lines[] = "Increase stamina +" . (($byte8 >= 9) ? "10" : "5");
        
        $all_opts = array_merge($setData['opts'], $setData['full_opts']);
        foreach ($all_opts as $opt) {
            $name = isset(self::$OPTION_NAMES[$opt[0]]) ? self::$OPTION_NAMES[$opt[0]] : "Unknown Option";
            $lines[] = $name . " +" . $opt[1];
        }
        return $lines;
    }
}