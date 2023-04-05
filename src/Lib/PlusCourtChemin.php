<?php

namespace App\PlusCourtChemin\Lib;

use App\PlusCourtChemin\Modele\DataObject\NoeudRoutier;
use App\PlusCourtChemin\Modele\Repository\NoeudRoutierRepository;

class PlusCourtChemin {
    private array $heuristique;
    private array $distances;
    private array $noeudsALaFrontiere = [];

    public function __construct(
        private NoeudRoutier $noeudRoutierDepart,
        private NoeudRoutier $noeudRoutierArrivee,
        private NoeudRoutierRepository $noeudRoutierRepository
    ) {
    }

    public function calculer(): ?float {
        $noeudRoutierDepartGid = $this->noeudRoutierDepart->getGid();
        $noeudRoutierArriveeGid = $this->noeudRoutierArrivee->getGid();
        $this->distances = [$noeudRoutierDepartGid => 0];
        $this->heuristique[$noeudRoutierDepartGid] = $this->calculerHeuristique($this->noeudRoutierDepart);
        $frontiere = [$noeudRoutierDepartGid => $this->distances[$noeudRoutierDepartGid] + $this->heuristique[$noeudRoutierDepartGid]];
        $visites = [$noeudRoutierDepartGid => true];
        while (!empty($frontiere)) {
            $noeudRoutierGidCourant = array_keys($frontiere, min($frontiere))[0];
            if ($noeudRoutierGidCourant === $noeudRoutierArriveeGid) return $this->distances[$noeudRoutierGidCourant];
            unset($frontiere[$noeudRoutierGidCourant]);
            $noeudRoutierCourant = $this->noeudRoutierRepository->recupererParClePrimaire($noeudRoutierGidCourant);
            $voisins = $noeudRoutierCourant->getVoisins();
            foreach ($voisins as $voisin) {
                $noeudVoisinGid = $voisin["noeud_routier_gid"];
                if (!isset($visites[$noeudVoisinGid])) {
                    $distanceTroncon = $voisin["longueur"];
                    $distanceProposee = $this->distances[$noeudRoutierGidCourant] + $distanceTroncon;
                    if (!isset($this->distances[$noeudVoisinGid]) || $distanceProposee < $this->distances[$noeudVoisinGid]) {
                        $this->distances[$noeudVoisinGid] = $distanceProposee;
                        $this->heuristique[$noeudVoisinGid] = $this->calculerHeuristique($this->noeudRoutierRepository->recupererParClePrimaire($noeudVoisinGid));
                        $frontiere[$noeudVoisinGid] = $distanceProposee + $this->heuristique[$noeudVoisinGid];
                        $visites[$noeudVoisinGid] = true; 
                    }
                }
            }
        }
    }

    private function calculerHeuristique($noeud): float {
        $longitudeDepart = $noeud->getLongitude();
        $latitudeDepart = $noeud->getLatitude();
        $longitudeArrivee = $this->noeudRoutierArrivee->getLongitude();
        $latitudeArrivee = $this->noeudRoutierArrivee->getLatitude();
        return $this->distanceEntreDeuxPointsHaversine($latitudeDepart, $longitudeDepart, $latitudeArrivee, $longitudeArrivee);
    }

    function distanceEntreDeuxPointsHaversine($lat1, $lon1, $lat2, $lon2) {
        $rayonTerre = 6371; 
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lon2 - $lon1);
        $c = 2 * asin(sqrt(sin($deltaPhi/2) * sin($deltaPhi/2) + cos($radLat1) * cos($radLat2) * sin($deltaLambda/2) * sin($deltaLambda/2)));        
        $distance = $rayonTerre * $c;
        return $distance;
    }

}