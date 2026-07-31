<?php
/**
 * Team Controller — Handle team creation, joining, and management
 */

class TeamController {
    /**
     * Helper to refresh session user data
     */
    private static function refreshUserSession(int $userId): void {
        $userRow = User::findById($userId);
        if ($userRow) {
            $_SESSION['user'] = [
                'id'              => $userRow['id'],
                'name'            => $userRow['name'],
                'email'           => $userRow['email'],
                'role'            => $userRow['role'],
                'department_id'   => $userRow['department_id'],
                'department_name' => $userRow['department_name'],
                'team_id'         => $userRow['team_id']
            ];
            // Re-issue cookie with updated team_id
            Auth::setCookie($userRow);
        }
    }

    /**
     * Show Join/Manage Team Page
     */
    public static function indexPage(): void {
        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);
        $team = null;
        $members = [];

        $usersWithoutTeam = [];
        $pendingTeamInvites = [];
        $pendingUserInvites = [];

        if (!empty($userDb['team_id'])) {
            $team = Team::findById($userDb['team_id']);
            if ($team) {
                $members = Team::getMembers($userDb['team_id']);
                if ($userDb['is_team_admin']) {
                    $usersWithoutTeam = Team::getUsersWithoutTeam($userDb['team_id']);
                    $pendingTeamInvites = Team::getPendingInvitesForTeam($userDb['team_id']);
                }
            }
        } else {
            $pendingUserInvites = Team::getPendingInvitesForUser($userDb['id']);
        }

        $view = 'team/index';
        $pageTitle = $team ? 'My Team: ' . htmlspecialchars($team['team_name']) : 'Join a Team';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    /**
     * Create a new team
     */
    public static function create(): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (!empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are already in a team. Leave your current team first.']);
            return;
        }

        $name = trim($_POST['team_name'] ?? '');
        $code = strtolower(trim($_POST['team_code'] ?? ''));
        $password = $_POST['team_password'] ?? '';

        // Validation
        $validator = new Validator();
        $validator->required('team_name', $name, 'Team Name')
                  ->required('team_code', $code, 'Team ID (Slug)')
                  ->required('team_password', $password, 'Team Password')
                  ->minLength('team_password', $password, 6, 'Team Password');

        if (!$validator->passes()) {
            Response::redirect('team', [
                'type' => 'error',
                'message' => implode(' ', $validator->errors())
            ]);
            return;
        }

        // Check unique code
        if (Team::findByCode($code)) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Team ID (Slug) already exists. Please choose a different one.']);
            return;
        }

        try {
            $teamId = Team::create([
                'team_name'          => $name,
                'team_code'          => $code,
                'team_password'      => $password,
                'created_by_user_id' => $currentUser['id']
            ]);

            // Auto-join as admin
            Team::join($currentUser['id'], $teamId, true);
            self::refreshUserSession($currentUser['id']);

            AuditLog::log('create', 'team', $teamId, [], ['team_name' => $name]);

            Response::redirect('team', ['type' => 'success', 'message' => 'Team created and joined successfully!']);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to create team: ' . $e->getMessage()]);
        }
    }

    /**
     * Join an existing team
     */
    public static function join(): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (!empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are already in a team. Leave your current team first.']);
            return;
        }

        $code = trim($_POST['team_code'] ?? '');
        $password = $_POST['team_password'] ?? '';

        if (empty($code) || empty($password)) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Please enter both Team ID and Password.']);
            return;
        }

        $team = Team::findByCode($code);
        if (!$team || !password_verify($password, $team['team_password'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid Team ID or Password.']);
            return;
        }

        try {
            Team::join($currentUser['id'], $team['id']);
            self::refreshUserSession($currentUser['id']);

            AuditLog::log('update', 'user', $currentUser['id'], [], ['action' => 'join_team', 'team_id' => $team['id']]);

            Response::redirect('team', ['type' => 'success', 'message' => 'Joined team "' . htmlspecialchars($team['team_name']) . '" successfully!']);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to join team: ' . $e->getMessage()]);
        }
    }

    /**
     * Leave the team
     */
    public static function leave(): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are not in any team.']);
            return;
        }

        $team = Team::findById($userDb['team_id']);
        if ($team && $team['created_by_user_id'] == $currentUser['id']) {
            Response::redirect('team', [
                'type' => 'error',
                'message' => 'As Team Admin, you cannot leave the team. You must disband the team to leave.'
            ]);
            return;
        }

        try {
            Team::leave($currentUser['id']);
            self::refreshUserSession($currentUser['id']);

            AuditLog::log('update', 'user', $currentUser['id'], [], ['action' => 'leave_team', 'team_id' => $userDb['team_id']]);

            Response::redirect('team', ['type' => 'success', 'message' => 'You have left the team.']);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to leave team: ' . $e->getMessage()]);
        }
    }

    /**
     * Disband the team (Team Admin only)
     */
    public static function disband(): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are not in any team.']);
            return;
        }

        $team = Team::findById($userDb['team_id']);
        if (!$team || $team['created_by_user_id'] != $currentUser['id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Only the Team Admin can disband this team.']);
            return;
        }

        try {
            Team::disband($team['id']);
            self::refreshUserSession($currentUser['id']);

            AuditLog::log('delete', 'team', $team['id'], [], ['team_name' => $team['team_name']]);

            Response::redirect('team', ['type' => 'success', 'message' => 'Team disbanded successfully. All member associations removed.']);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to disband team: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove a member from the team (Team Admin only)
     */
    public static function removeMember(int $targetUserId): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are not in any team.']);
            return;
        }

        $team = Team::findById($userDb['team_id']);
        if (!$team || !$userDb['is_team_admin']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Only Team Admins can remove members.']);
            return;
        }

        $targetUser = User::findById($targetUserId);
        if (!$targetUser || $targetUser['team_id'] != $team['id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Member not found or not in your team.']);
            return;
        }

        if ($targetUser['id'] == $currentUser['id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You cannot remove yourself. Disband the team instead.']);
            return;
        }

        if ($targetUser['id'] == $team['created_by_user_id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'The creator of the team cannot be removed.']);
            return;
        }

        try {
            Team::leave($targetUserId);
            // If the removed member is logged in, their session will be refreshed next time they authenticate, 
            // or we could force a refresh. We'll log it:
            AuditLog::log('update', 'user', $targetUserId, [], ['action' => 'removed_from_team', 'team_id' => $team['id']]);

            Response::redirect('team', ['type' => 'success', 'message' => 'Member removed successfully.']);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to remove member: ' . $e->getMessage()]);
        }
    }

    /**
     * Change team password (Team Admin only)
     */
    public static function changePassword(): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are not in any team.']);
            return;
        }

        $team = Team::findById($userDb['team_id']);
        if (!$team || !$userDb['is_team_admin']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Only Team Admins can change this team\'s password.']);
            return;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        if (!password_verify($currentPassword, $team['team_password'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Incorrect current team password.']);
            return;
        }

        $newPassword = $_POST['team_password'] ?? '';
        if (empty($newPassword) || strlen($newPassword) < 6) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Team password must be at least 6 characters.']);
            return;
        }

        try {
            Team::updatePassword($team['id'], $newPassword);
            AuditLog::log('update', 'team', $team['id'], [], ['action' => 'change_team_password']);
            Response::redirect('team', ['type' => 'success', 'message' => 'Team password updated successfully.']);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to update team password: ' . $e->getMessage()]);
        }
    }

    /**
     * Promote another member to Team Admin (Team Admin only)
     */
    public static function makeAdmin(int $targetUserId): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are not in any team.']);
            return;
        }

        $team = Team::findById($userDb['team_id']);
        if (!$team || $team['created_by_user_id'] != $currentUser['id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Only the Team Admin can promote members.']);
            return;
        }

        $targetUser = User::findById($targetUserId);
        if (!$targetUser || $targetUser['team_id'] != $team['id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Member not found or not in your team.']);
            return;
        }

        if ($targetUser['id'] == $currentUser['id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are already the Team Admin.']);
            return;
        }

        try {
            Team::updateAdmin($team['id'], $targetUserId);
            AuditLog::log('update', 'team', $team['id'], [], [
                'action' => 'change_admin',
                'old_admin' => $currentUser['id'],
                'new_admin' => $targetUserId
            ]);

            Response::redirect('team', ['type' => 'success', 'message' => 'Team Admin role successfully transferred to ' . htmlspecialchars($targetUser['name']) . '.']);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to promote member: ' . $e->getMessage()]);
        }
    }

    /**
     * Admin view of all teams
     */
    public static function adminList(): void {
        AuthMiddleware::requireAdmin();
        $teams = Team::allWithStats();
        $view  = 'team/admin_list';
        $pageTitle = 'Manage Teams';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    /**
     * Admin disband team
     */
    public static function adminDisband(int $teamId): void {
        AuthMiddleware::requireAdmin();
        if (!CSRF::validate()) {
            Response::redirect('admin/teams', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        try {
            $team = Team::findById($teamId);
            if (!$team) {
                Response::redirect('admin/teams', ['type' => 'error', 'message' => 'Team not found.']);
                return;
            }
            Team::disband($teamId);
            AuditLog::log('delete', 'team', $teamId, [], ['team_name' => $team['team_name'], 'admin_disband' => true]);
            Response::redirect('admin/teams', ['type' => 'success', 'message' => 'Team "' . htmlspecialchars($team['team_name']) . '" disbanded successfully.']);
        } catch (Exception $e) {
            Response::redirect('admin/teams', ['type' => 'error', 'message' => 'Failed to disband team: ' . $e->getMessage()]);
        }
    }

    /**
     * Send an invitation to a coworker (Team Admin only)
     */
    public static function inviteMember(): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are not in any team.']);
            return;
        }

        if (!$userDb['is_team_admin']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Only Team Admins can send invitations.']);
            return;
        }

        $targetUserId = (int)($_POST['user_id'] ?? 0);
        if ($targetUserId <= 0) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Please select a valid user to invite.']);
            return;
        }

        $targetUser = User::findById($targetUserId);
        if (!$targetUser || !empty($targetUser['team_id']) || $targetUser['is_active'] != 1) {
            Response::redirect('team', ['type' => 'error', 'message' => 'User not found, already in a team, or inactive.']);
            return;
        }

        try {
            Team::sendInvite($userDb['team_id'], $targetUserId, $currentUser['id']);
            AuditLog::log('create', 'team_invitation', $targetUserId, [], [
                'team_id' => $userDb['team_id'],
                'invited_by' => $currentUser['id']
            ]);

            Response::redirect('team', [
                'type' => 'success',
                'message' => 'Invitation sent successfully to ' . htmlspecialchars($targetUser['name']) . '.'
            ]);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to send invitation: ' . $e->getMessage()]);
        }
    }

    /**
     * Accept a pending team invitation
     */
    public static function acceptInvite(int $inviteId): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (!empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are already in a team. Leave your current team first.']);
            return;
        }

        $invite = Team::findInviteById($inviteId);
        if (!$invite || $invite['user_id'] != $currentUser['id'] || $invite['status'] !== 'pending') {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invitation not found or invalid.']);
            return;
        }

        try {
            Team::acceptInvite($inviteId);
            
            // Refresh user session to apply team_id change
            self::refreshUserSession($currentUser['id']);

            AuditLog::log('update', 'team_invitation', $inviteId, [], [
                'action' => 'invite_accepted',
                'team_id' => $invite['team_id'],
                'user_id' => $currentUser['id']
            ]);

            Response::redirect('team', [
                'type' => 'success',
                'message' => 'You have joined the team successfully!'
            ]);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to accept invitation: ' . $e->getMessage()]);
        }
    }

    /**
     * Decline a pending team invitation
     */
    public static function declineInvite(int $inviteId): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $invite = Team::findInviteById($inviteId);
        if (!$invite || $invite['user_id'] != $currentUser['id'] || $invite['status'] !== 'pending') {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invitation not found or invalid.']);
            return;
        }

        try {
            Team::declineInvite($inviteId);
            AuditLog::log('update', 'team_invitation', $inviteId, [], [
                'action' => 'invite_declined',
                'team_id' => $invite['team_id'],
                'user_id' => $currentUser['id']
            ]);

            Response::redirect('team', [
                'type' => 'success',
                'message' => 'Invitation declined successfully.'
            ]);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to decline invitation: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel/withdraw a pending team invitation (Team Admin only)
     */
    public static function cancelInvite(int $inviteId): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are not in any team.']);
            return;
        }

        if (!$userDb['is_team_admin']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Only Team Admins can cancel invitations.']);
            return;
        }

        $invite = Team::findInviteById($inviteId);
        if (!$invite || $invite['team_id'] != $userDb['team_id'] || $invite['status'] !== 'pending') {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invitation not found or invalid.']);
            return;
        }

        try {
            Team::cancelInvite($inviteId);
            AuditLog::log('delete', 'team_invitation', $inviteId, [], [
                'action' => 'invite_cancelled',
                'team_id' => $invite['team_id'],
                'user_id' => $invite['user_id']
            ]);

            Response::redirect('team', [
                'type' => 'success',
                'message' => 'Invitation withdrawn successfully.'
            ]);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to cancel invitation: ' . $e->getMessage()]);
        }
    }

    /**
     * Update team name (Team Admin only)
     */
    public static function updateDetails(): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are not in any team.']);
            return;
        }

        if (!$userDb['is_team_admin']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Only Team Admins can update team details.']);
            return;
        }

        $name = trim($_POST['team_name'] ?? '');
        $code = strtolower(trim($_POST['team_code'] ?? ''));

        if (empty($name) || empty($code)) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Team Name and Team ID cannot be empty.']);
            return;
        }

        $team = Team::findById($userDb['team_id']);
        if (!$team) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Team not found.']);
            return;
        }

        // Check uniqueness of code if changed
        if ($code !== $team['team_code']) {
            $existing = Team::findByCode($code);
            if ($existing) {
                Response::redirect('team', ['type' => 'error', 'message' => 'Team ID (Slug) already exists. Choose a different one.']);
                return;
            }
        }

        try {
            Team::updateTeamName($userDb['team_id'], $name, $code);
            AuditLog::log('update', 'team', $userDb['team_id'], [], [
                'action' => 'update_details',
                'team_name' => $name,
                'team_code' => $code
            ]);

            Response::redirect('team', ['type' => 'success', 'message' => 'Team details updated successfully.']);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to update team details: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle a member's team admin status (Team Admin only)
     */
    public static function toggleAdminStatus(int $targetUserId): void {
        if (!CSRF::validate()) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $userDb = User::findById($currentUser['id']);

        if (empty($userDb['team_id'])) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You are not in any team.']);
            return;
        }

        if (!$userDb['is_team_admin']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Only Team Admins can modify member roles.']);
            return;
        }

        $targetUser = User::findById($targetUserId);
        if (!$targetUser || $targetUser['team_id'] != $userDb['team_id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Member not found or not in your team.']);
            return;
        }

        $team = Team::findById($userDb['team_id']);

        // Cannot toggle/remove admin status from the original team creator/owner
        if ($targetUser['id'] == $team['created_by_user_id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'The original Team Creator cannot be demoted.']);
            return;
        }

        // Prevent self-demotion to ensure at least one admin remains
        if ($targetUser['id'] == $currentUser['id']) {
            Response::redirect('team', ['type' => 'error', 'message' => 'You cannot demote yourself.']);
            return;
        }

        try {
            $newAdminStatus = !$targetUser['is_team_admin'];
            Team::setAdminStatus($targetUserId, $newAdminStatus);

            AuditLog::log('update', 'user', $targetUserId, [], [
                'action' => $newAdminStatus ? 'promoted_to_team_admin' : 'demoted_from_team_admin',
                'team_id' => $userDb['team_id']
            ]);

            $statusText = $newAdminStatus ? 'promoted to Team Admin' : 'dismissed as Team Admin';
            Response::redirect('team', [
                'type' => 'success',
                'message' => htmlspecialchars($targetUser['name']) . ' has been successfully ' . $statusText . '.'
            ]);
        } catch (Exception $e) {
            Response::redirect('team', ['type' => 'error', 'message' => 'Failed to modify role: ' . $e->getMessage()]);
        }
    }
}
