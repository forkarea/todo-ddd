<?php

namespace spec\Application\Task;

use Application\Task\Command;
use Domain\Exception\TaskNameIsAlreadyExistedException;
use Domain\Exception\TaskNameIsEmptyException;
use Domain\Factory\TaskFactory;
use Domain\Repository\TaskRepositoryInterface;
use Domain\Task;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CommandSpec extends ObjectBehavior
{
    /** @var TaskRepositoryInterface */
    protected $taskRepository;

    /** @var TaskFactory */
    protected $taskFactory;

    protected $tasks;


    /** @var Task */
    protected $newTask;


    /** @var Task */
    protected $remainingTask;

    /** @var Task */
    protected $completedTask;

    function it_is_initializable()
    {
        $this->shouldHaveType(Command::class);
    }

    function generate_task(string $name) : Task
    {
        $task = new Task();
        $task->setName($name);

        return $task;
    }

    function let(TaskRepositoryInterface $taskRepository, TaskFactory $taskFactory)
    {
        $this->newTask = $this->generate_task('Buying salt');
        $this->newTask->setStatus(Task::STATUS_REMAINING);

        $this->remainingTask = $this->generate_task('Buying sugar');
        $this->remainingTask->setId(1);
        $this->remainingTask->setStatus(Task::STATUS_REMAINING);

        $this->completedTask = $this->generate_task('Buying milk');
        $this->remainingTask->setId(2);
        $this->completedTask->setStatus(Task::STATUS_COMPLETED);

        $this->tasks = [
            $this->remainingTask,
            $this->completedTask
        ];


        $this->taskRepository = $taskRepository;
        $this->taskFactory = $taskFactory;

        $this->taskFactory->createFromName($this->newTask->getName())
            ->willReturn($this->newTask);

        $this->taskFactory->createFromName('')
            ->willThrow(TaskNameIsEmptyException::class);

        $this->taskFactory->createFromName($this->remainingTask->getName())
            ->willThrow(TaskNameIsAlreadyExistedException::class);

        $this->taskRepository->find($this->remainingTask->getId())
            ->willReturn($this->remainingTask);

        $this->taskRepository->remove($this->remainingTask)
            ->willReturn(true);

        $this->taskRepository->save($this->newTask)
            ->willReturn(true);

        $this->taskRepository->save($this->remainingTask)
            ->willReturn(true);
        $this->taskRepository->save($this->completedTask)
            ->willReturn(true);
        $this->taskRepository->removeByStatus(Task::STATUS_COMPLETED)
            ->willReturn(true);

        $this->beConstructedWith($this->taskRepository, $this->taskFactory);
    }

    function it_can_add_new_task()
    {
        $this->addNewTask($this->newTask->getName());
    }

    function it_cannot_add_existed_task()
    {
        $this->shouldThrow(TaskNameIsAlreadyExistedException::class)
            ->duringAddNewTask($this->remainingTask->getName());
    }

    function it_cannot_add_empty_task()
    {
        $this->shouldThrow(TaskNameIsEmptyException::class)
            ->duringAddNewTask('');
    }



    function it_can_complete_task()
    {
        $task = $this->completeTask($this->remainingTask);

        $task->getStatus()->shouldBe(Task::STATUS_COMPLETED);
    }

    function it_can_redo_task()
    {
        $task = $this->redoTask($this->completedTask);

        $task->getStatus()->shouldBe(Task::STATUS_REMAINING);
    }

    function it_can_edit_task()
    {
        $task = $this->editTask(
            $this->remainingTask->getId(),
            [
                'name' => 'New name',
                'status' => Task::STATUS_COMPLETED
            ]
        );

        $task->getName()->shouldBe('New name');
        $task->getStatus()->shouldBe(Task::STATUS_COMPLETED);
    }


    function it_can_remove_task()
    {
        $this->removeTask(
            $this->remainingTask->getId(),
            [
                'name' => 'New name',
                'status' => Task::STATUS_COMPLETED
            ]
        );
    }

    function it_can_clean_completed_task()
    {
        $this->cleanAllCompletedTasks();
    }

}
