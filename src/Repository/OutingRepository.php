<?php

namespace App\Repository;

use App\Data\SearchData;
use App\Entity\Outing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Outing|null find($id, $lockMode = null, $lockVersion = null)
 * @method Outing|null findOneBy(array $criteria, array $orderBy = null)
 * @method Outing[]    findAll()
 * @method Outing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OutingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Outing::class);
    }

    /**
     * Récupère les sorties en lien avec une recherche
     * @param SearchData $search
     * @param $user
     * @return Outing[]
     */
    public function findSearch(SearchData $search, $user): array
    {


        $query = $this
            ->createQueryBuilder('outing');


        if (!empty($search->getMotCle())) {
            $query = $query
                ->andWhere('outing.name LIKE :motCle')
                ->setParameter('motCle', "%{$search->getMotCle()}%");
        }

        if ($search->getBeginDate() != null) {
            $query = $query
                ->andWhere('outing.startDate >= :beginDate')
                ->setParameter('beginDate', $search->getBeginDate());
        }
        if ($search->getEndDate() != null) {
            $query = $query
                ->andWhere('outing.startDate <= :endDate')
                ->setParameter('endDate', $search->getEndDate());
        }
        if (!empty($search->getDureeMin())) {
            $query = $query
                ->andWhere('outing.duration >= :dureeMin')
                ->setParameter('dureeMin', $search->getDureeMin());
        }
        if (!empty($search->getDureeMax())) {
            $query = $query
                ->andWhere('outing.duration <= :dureeMax')
                ->setParameter('dureeMax', $search->getDureeMax());
        }
        if (!empty($search->getOrga())) {
            if ($search->getOrga() == 1) {
                $query = $query
                    ->addSelect('i')
                    ->leftJoin('outing.member', 'i')
                    ->andWhere('i = :organisateur')
                    ->setParameter('organisateur', $user);

            }

        }

        if(!empty($search->getInscrit()) || !empty($search->getNotInscrit())){
            if ($search->getInscrit() == 1 && $search->getNotInscrit() == 1){
//nothing to do
            } else{
                if ($search->getInscrit() == 1){
                    $query = $query
                        ->addSelect('s')
                        ->innerJoin('outing.subscriptions', 's')
//                    ->innerJoin('outing.member', 'm')
                        ->andWhere('s.member = :inscrit')
//                    ->andWhere('m = :inscrit')
//                    ->andWhere('m = :inscrit OR s = :inscrit')
                        ->setParameter('inscrit', $user);
                }

                if ($search->getNotInscrit() == 1) {
                    $subQb = $this->createQueryBuilder('sq')
                        ->innerJoin('sq.subscriptions', 'sqb')
                        ->Where('sqb.member = :user');
                    $query = $query
                        ->addselect('unsub')
                        ->leftJoin('outing.subscriptions', 'unsub')
                        ->andWhere('outing NOT IN ('. $subQb->getDQL().')')
                        ->setParameter(':user', $user);
//                    dump($user);
//                    dump($search->getNotInscrit());


                }
//                if ($search->getNotInscrit() == 1) {
//                    $subQb = $this->createQueryBuilder('sq')
//                        ->addselect('sqb')
//                        ->innerJoin('sq.subscriptions', 'sqb')
//                        ->Where('sqb.member = :user')
//                        ->setParameter('user', $user);
//                    $query = $query
//                        ->addselect('unsub')
//                        ->leftJoin('outing.subscriptions', 'unsub')
//                        ->andWhere('outing NOT IN ('. $subQb->getDQL().')')
//                        ->setParameter(':user', $user);
//                    dump($user);
////                    dump($search->getNotInscrit());
//
//
//                }
            }


        }

//        if (!empty($search->getInscrit())) {
//            if ($search->getInscrit() == 1) {
//                $query = $query
//                    ->addSelect('s')
//                    ->innerJoin('outing.subscriptions', 's')
////                    ->innerJoin('outing.member', 'm')
//                    ->andWhere('s.member = :inscrit')
////                    ->andWhere('m = :inscrit')
////                    ->andWhere('m = :inscrit OR s = :inscrit')
//                    ->setParameter('inscrit', $user);
//
//            }
//
//        }
//        if (!empty($search->getNotInscrit())) {
//            if ($search->getNotInscrit() == 1) {
//                $query = $query
//                    ->addSelect('sa')
//                    ->innerjoin('outing.subscriptions', 'sa')
//                    ->andWhere('sa.member != :notinscrit')
//                    ->setParameter('notinscrit', $user);
//
//            }
//
//        }

//        if (!empty($search->getPassee())) {
//            if ($search->getPassee() == 1) {
//                $query = $query
//                    ->andWhere('outing.state = :passe')
//                    ->setParameter('passe', "5");
//
//            }
//
//        }
        if (!empty($search->getPassee())) {
            if ($search->getPassee() == 1) {
                $query = $query
                    ->addSelect('e')
                    ->leftJoin('outing.state', 'e')
                    ->andWhere('e.label = :passe')
                    ->setParameter('passe', "Passée");

            }

        }


        return $query->getQuery()->getResult();
    }


    /**
     * Récupère les sorties devant être archivées
     * @param $lastMonth
     * @return Outing[]
     */
   public function findToArchive($lastMonth): array
   {
       $entityManager = $this->getEntityManager();
       $dql           = <<<DQL
SELECT o
FROM APP\ENTITY\Outing o
WHERE o.startDate < :lastMonth
DQL;
       $query = $entityManager
           ->createQuery($dql)
           ->setParameter(':lastMonth', $lastMonth);
//       dump($lastMonth);

       return $query->getResult();
   }

    /**
     * Récupère les sorties devant être clôturées
     * @param $limiteNow
     * @return Outing[]
     */
    public function findToClosure($limiteNow): array
    {
        $entityManager = $this->getEntityManager();
        $dql           = <<<DQL
SELECT ab
FROM APP\ENTITY\Outing ab
WHERE ab.limitDateSub <= :limiteNow
DQL;
        $query = $entityManager
            ->createQuery($dql)
            ->setParameter(':limiteNow', $limiteNow);
//       dump($limiteNow);

        return $query->getResult();
    }


}
