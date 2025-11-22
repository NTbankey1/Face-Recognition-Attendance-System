

<div class="table">
    <table>
        <thead>
            <tr>
                <th>Registration No</th>
                <th>Name</th>
                <th>Course</th>
                <th>Unit</th>
                <th>Venue</th>
                <th>Attendance</th>
                <th>Settings</th>
            </tr>
        </thead>
        <tbody id="studentTableContainer">
            <?php
            $students = $selectedStudents ?? [];
            if (!empty($students)) {
                $courseID = htmlspecialchars($selectedCourseID ?? '', ENT_QUOTES, 'UTF-8');
                $unitID = htmlspecialchars($selectedUnitID ?? '', ENT_QUOTES, 'UTF-8');
                $venueID = htmlspecialchars($selectedVenue ?? '', ENT_QUOTES, 'UTF-8');

                foreach ($students as $student) {
                    $registrationNumber = htmlspecialchars($student['registrationNumber'], ENT_QUOTES, 'UTF-8');
                    $name = htmlspecialchars(trim($student['firstName'] . ' ' . $student['lastName']), ENT_QUOTES, 'UTF-8');

                    echo '<tr>';
                    echo "<td>{$registrationNumber}</td>";
                    echo "<td>{$name}</td>";
                    echo "<td>{$courseID}</td>";
                    echo "<td>{$unitID}</td>";
                    echo "<td>{$venueID}</td>";
                    echo '<td>Absent</td>';
                    echo "<td><span><i class='ri-edit-line edit'></i><i class='ri-delete-bin-line delete'></i></span></td>";
                    echo '</tr>';
                }
            } else {
                echo "<tr><td colspan='7'>No records found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
