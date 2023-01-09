<?php
    $PageTitle = "Settings";
    require '../header.php';
    ?>

<style>
    tr label{
        text-align:right;
        width:100%;
        display:inline-block;
    }
    td{
        vertical-align:top;
        padding: 1rem;
    }
    td input{
        margin: 0.25rem;
    }
</style>
<h1>Settings</h1>
<hr>

<form>
    <table>
        <tr>
            <td>

            </td>
            <td>
                Update username button here.<Br>
                <span class="subtext">Useful if you've had a namechange.</span>
            </td>
        </tr>
        <tr>
            <td>
                <label>Random behaviour:</label><br>
            </td>
            <td>
                <select>
                    <option>Prioritise Played</option>
                    <option>True Random</option>
                </select><br>
                <span class="subtext">"Prioritise Played" only works if you have osu! supporter.</span>
            </td>
        </tr>
        <tr>
            <td>
                <label>Default charts to:</label>
            </td>
            <td>
                <select>
                    <option>Current Year</option>
                    <option>Alltime</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label>Custom rating names:</label>
            </td>
            <td>
                5.0 <input autocomplete="off" placeholder="5.0"/><br>
                4.5 <input autocomplete="off" placeholder="4.5"/><br>
                4.0 <input autocomplete="off" placeholder="4.0"/><br>
                3.5 <input autocomplete="off" placeholder="3.5"/><br>
                3.0 <input autocomplete="off" placeholder="3.0"/><br>
                2.5 <input autocomplete="off" placeholder="2.5"/><br>
                2.0 <input autocomplete="off" placeholder="2.0"/><br>
                1.5 <input autocomplete="off" placeholder="1.5"/><br>
                1.0 <input autocomplete="off" placeholder="1.0"/><br>
                0.5 <input autocomplete="off" placeholder="0.5"/><br>
                0.0 <input autocomplete="off" placeholder="0.0"/><br>
            </td>
        </tr>
    </table>
</form>

<hr>

<h2>Api Stutff</h2>

coming soon

<?php
require '../footer.php';
?>