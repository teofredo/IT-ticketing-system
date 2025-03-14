<form method="POST">
    <input type="text" name="name" placeholder="Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role">
        <option value="employee">Employee</option>
        <option value="technician">Technician</option>
        <option value="admin">Admin</option>
    </select>
    <input type="number" name="department_id" placeholder="Department ID" required>
    <button type="submit">Register</button>
</form>