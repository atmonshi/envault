<?php

namespace App\Livewire\Apps\Edit;

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Collaborators extends Component
{
    use AuthorizesRequests;

    /**
     * @var \App\Models\App
     */
    public $app;

    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public $globalAdmins;

    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public $addableUsers;

    /**
     * @var int
     */
    public $userToAddId;

    /**
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function add()
    {
        $this->authorize('update', $this->app);

        $this->validate([
            'userToAddId' => ['required', 'exists:users,id'],
        ]);

        $userToAdd = User::findOrFail($this->userToAddId);

        $this->app->collaborators()->attach($this->userToAddId);

        $this->dispatch('app.collaborator.added', userToAddId:$this->userToAddId, appId:$this->app->id);

        event(new \App\Events\Apps\CollaboratorAddedEvent($this->app, $userToAdd));

        $this->mount($this->app->refresh());
    }

    /**
     * @param \App\Models\App $app
     * @return void
     */
    public function mount(App $app)
    {
        $this->app = $app;

        $this->globalAdmins = User::whereIn('role', ['admin', 'owner'])->get();

        $this->addableUsers = User::whereNotIn('role', ['admin', 'owner'])->orWhereNull('role')->get()->filter(function (User $user): bool {
            return ! $user->isAppCollaborator($this->app);
        });

        if (count($this->addableUsers)) {
            $this->userToAddId = $this->addableUsers->first()->id;
        }
    }

    /**
     * @param int $id
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function remove($id)
    {
        $this->authorize('update', $this->app);

        $userToRemove = User::findOrFail($id);

        $this->app->collaborators()->detach($id);

        $this->dispatch('app.collaborator.removed', id:$id, appId:$this->app->id);

        event(new \App\Events\Apps\CollaboratorRemovedEvent($this->app, $userToRemove));

        $this->mount($this->app->refresh());
    }

    /**
     * @param int $id
     * @param string|null $role
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateRole($id, $role)
    {
        $this->authorize('update', $this->app);

        $userToUpdate = User::findOrFail($id);

        $oldRole = $this->app->collaborators()->findOrFail($id)->pivot->role;

        $this->app->collaborators()->updateExistingPivot($id, [
            'role' => $role,
        ]);

        $this->dispatch('app.collaborator.updated', id:$id, appId:$this->app->id);

        event(new \App\Events\Apps\CollaboratorRoleUpdatedEvent($this->app, $userToUpdate, $oldRole, $role));

        $this->mount($this->app->refresh());
    }

    /**
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('apps.edit.collaborators');
    }
}
