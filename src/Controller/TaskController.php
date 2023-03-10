<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskController extends AbstractController
{

    #[Route('/tasks-to-do', name: 'tasks_to_do')]
    public function tasksToDo(
        TaskRepository $taskRepository,
        UserRepository $userRepository
    ): Response {
        return $this->render('task/todo.html.twig', [
            'tasks' => $taskRepository->findBy(['isDone' => false]),
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/tasks', name: 'tasks_list')]
    public function tasksList(
        TaskRepository $taskRepository,
        UserRepository $userRepository
    ): Response {
        return $this->render('task/list.html.twig', [
            'tasks' => $taskRepository->findAll(),
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/tasks-completed', name: 'tasks_completed')]
    public function tasksCompleted(
        TaskRepository $taskRepository,
        UserRepository $userRepository
    ): Response {
        return $this->render('task/completed.html.twig', [
            'tasks' => $taskRepository->findBy(['isDone' => true]),
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/tasks/create', name: 'task_create')]
    public function createAction(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $task = new Task();
        $form = $this
            ->createForm(TaskType::class, $task)
            ->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid()) {

            $user = $this->getUser();
            $task->setUser($user);
            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'La t??che a ??t?? bien ??t?? ajout??e.');

            return $this->redirectToRoute('tasks_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/tasks/{id}/edit', name: 'task_edit')]
    public function editAction(
        Task $task,
        Request $request,
        EntityManagerInterface $em,
        Security $security
    ): Response {
        $form = $this
            ->createForm(TaskType::class, $task)
            ->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid()) {

            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'La t??che a bien ??t?? modifi??e.');

            return $this->redirectToRoute('tasks_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }


    #[Route('/tasks/{id}/toggle', name: 'task_toggle')]
    public function toggleTaskAction(
        Task $task,
        EntityManagerInterface $em
    ): Response {
        $task->toggle(!$task->isDone());
        $em->persist($task);
        $em->flush();

        if($task->isDone() === true){
            $this->addFlash('success', sprintf('La t??che %s a bien ??t?? marqu??e comme faite.', $task->getTitle()));
            return $this->redirectToRoute('tasks_list');
        }

        $this->addFlash('success', sprintf('La t??che %s a bien ??t?? marqu??e comme ?? faire.', $task->getTitle()));

        return $this->redirectToRoute('tasks_list');
    }


    #[Route('/tasks/{id}/delete', name: 'task_delete')]
    public function deleteTaskAction(
        Task $task,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();

            $this->addFlash('success', 'La t??che a bien ??t?? supprim??e.');
        }

        return $this->redirectToRoute('tasks_list');
    }
}
