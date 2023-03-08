@extends('layouts.master')

@section('title', 'Settings')

@section('content')

  <style>
    tr label {
      text-align: right;
      width: 100%;
      display: inline-block;
    }

    td {
      vertical-align: top;
      padding: 1rem;
    }

    td input {
      margin: 0.25rem;
    }

    input {
      margin: 0px;
    }

    summary {
      cursor: pointer;
    }
  </style>

  <h1>Settings</h1>
  <hr>

  @php
    $user = Auth::user();
    $custom_ratings = json_decode($user->custom_ratings, true);
  @endphp

  <form>
    <table>
      <tr>
        <td>
          <label>Random behaviour:</label><br>
        </td>
        <td>
          <select name="RandomBehaviour" id="RandomBehaviour" autocomplete="off">
            <option value="0">Prioritise Played</option>
            <option value="1" <?php
            /* if ($user["DoTrueRandom"]==1) { echo 'selected="selected"'; } */
            ?>>True Random</option>
          </select><br>
          <span class="subText">"Prioritise Played" only works if you have osu!
            supporter.</span>
        </td>
      </tr>

      <tr>
        <td>
          <label>Custom rating names:</label>
        </td>
        <td>
          @for ($r = 5.0; $r >= 0.0; $r -= 0.5)
            @php
              $rs = number_format($r, 1);
              $rs2 = str_replace('.', '', $rs);
            @endphp

            <input autocomplete="off" id="{{ $rs2 }}Name"
              placeholder="{{ $rs }}" maxlength="40"
              value="{{ $custom_ratings[$rs] ?? '' }}" />
            {{ $rs }}
            <br />
          @endfor
        </td>
      </tr>
      <tr>
        <td>
        </td>
        <td>
          <button type='button' onclick="saveChanges()">Save changes</button>
          <span id="statusText"></span>
        </td>
      </tr>
    </table>
  </form>
  <hr>
  <h2>API</h2>
  <a href="https://github.com/apollo-dw/omdb/wiki/API" target="_blank"
    rel="noopener noreferrer">Click to view the (bare bones)
    documentations.</a><br>
  <span class="subText">Please keep your API key secure - if it leaks then it's as
    bad as having your PASSWORD leaked.<br> Click your application name to REVEAL
    your API key.</span><br><br>

  @foreach ($api_keys as $api_key)
    <details>
      <summary>
        {{ $api_key->name }}

        <form action="/settings/delete_api_key" method="post" style="display:
        inline-block;">
          @csrf
          <button type="submit" style="display: inline-block; background: none;  color: inherit;  border: none;">
            <input type="hidden" name="api_key" value="{{ $api_key->api_key }}" />
          <i class='icon-remove'></i>
          </button>
        </a>
        </form>
      </summary>
      <span class='subText'>
        {{ $api_key->api_key }}
      </span>
    </details>
  @endforeach

  <form action="/settings/api_key" method="post">
    <table>
      <tr>
        <td>
          <label>New Application Name:</label><br>
        </td>
        <td>
          @csrf
          <input type="text" autocomplete="off" name="apiname" id="apiname"
            placeholder="omdb application" maxlength="255" minlength="1"
            value="" required><br>
        </td>
      </tr>
      <tr>
        <td>
        </td>
        <td>
          <button>Create new application</button>
        </td>
      </tr>
    </table>
  </form>

  <script>
    function saveChanges() {
      const custom_ratings = [
        document.getElementById("50Name").value,
        document.getElementById("45Name").value,
        document.getElementById("40Name").value,
        document.getElementById("35Name").value,
        document.getElementById("30Name").value,
        document.getElementById("25Name").value,
        document.getElementById("20Name").value,
        document.getElementById("15Name").value,
        document.getElementById("10Name").value,
        document.getElementById("05Name").value,
        document.getElementById("00Name").value,
      ];

      const random_behavior = document.getElementById("RandomBehaviour").value ==
        "1";

      fetch("/settings", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": "{{ csrf_token() }}",
        },
        body: JSON.stringify({
          random_behavior,
          custom_ratings
        }),
      }).then(response => {
        if (response.status == 200) {
          document.getElementById("statusText").textContent = "Saved!";
          document.getElementById("statusText").style = "display:inline;"
          $("#statusText").fadeOut(3000, "linear", function() {});
        }
      });
    }
  </script>

@endsection
