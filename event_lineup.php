<?php
/**
 * Event Lineups Page - AFB Mangaan Attendance System
 */

$pageTitle = 'Event Lineups';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/attendance_logic.php';
require_once __DIR__ . '/functions/csrf.php';

$pdo = getDB();
$message = '';
$error = '';
$church = $_SESSION['church'] ?? 'AFB Mangaan';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_song' || $action === 'edit_song') {
            $id = $_POST['song_id'] ?? null;
            $eventId = $_POST['event_id'] ?? null;
            $title = trim($_POST['title'] ?? '');
            $artist = trim($_POST['artist'] ?? '');
            $lyrics = $_POST['lyrics'] ?? '';
            $chords = $_POST['chords'] ?? '';
            $sortOrder = $_POST['sort_order'] ?? 0;
            
            try {
                if ($action === 'edit_song' && $id) {
                    $stmt = $pdo->prepare("UPDATE event_songs SET title=?, artist=?, lyrics=?, chords=?, sort_order=? WHERE id=? AND event_id IN (SELECT id FROM events WHERE church=?)");
                    $stmt->execute([$title, $artist, $lyrics, $chords, $sortOrder, $id, $church]);
                    $message = 'Song updated successfully';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO event_songs (event_id, title, artist, lyrics, chords, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$eventId, $title, $artist, $lyrics, $chords, $sortOrder]);
                    $message = 'Song added successfully';
                }
            } catch (PDOException $e) {
                $error = 'Error saving song: ' . $e->getMessage();
            }
        } elseif ($action === 'delete_song' && !empty($_POST['song_id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM event_songs WHERE id=? AND event_id IN (SELECT id FROM events WHERE church=?)");
                $stmt->execute([$_POST['song_id'], $church]);
                $message = 'Song removed successfully';
            } catch (PDOException $e) {
                $error = 'Error deleting song';
            }
        }
    }
}

// Get upcoming and ongoing events
$eventsStmt = $pdo->prepare("SELECT id, event_name, start_date, status FROM events WHERE church = ? AND status IN ('Upcoming', 'Ongoing') ORDER BY start_date ASC");
$eventsStmt->execute([$church]);
$events = $eventsStmt->fetchAll();

$selectedEventId = $_GET['event_id'] ?? ($events[0]['id'] ?? null);

$songs = [];
if ($selectedEventId) {
    $songsStmt = $pdo->prepare("SELECT * FROM event_songs WHERE event_id = ? ORDER BY sort_order ASC, id ASC");
    $songsStmt->execute([$selectedEventId]);
    $songs = $songsStmt->fetchAll();
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php if ($message): ?>
    <div class="alert badge-success" style="margin-bottom: 1rem; padding: 1rem;">
        <i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert badge-danger" style="margin-bottom: 1rem; padding: 1rem;">
        <i class="ph ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="card animate__animated animate__fadeIn">
    <div class="card-header">
        <h3 class="card-title">Select Event</h3>
    </div>
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label class="form-label">Event</label>
                <select name="event_id" class="form-control form-select" onchange="this.form.submit()">
                    <?php if (empty($events)): ?>
                        <option value="">No upcoming events</option>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo $event['id']; ?>" <?php echo $selectedEventId == $event['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($event['event_name']) . ' (' . date('M d, Y', strtotime($event['start_date'])) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedEventId): ?>
    <div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title"><i class="ph ph-music-notes"></i> Lineup Songs</h3>
            <button type="button" class="btn btn-success" onclick="openSongModal()">
                <i class="ph ph-plus"></i> Add Song
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($songs)): ?>
                <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 12px; border: 1px dashed var(--border-primary);">
                    <i class="ph ph-music-notes" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; display: block;"></i>
                    <h4 style="margin: 0 0 0.5rem 0;">No Songs Yet</h4>
                    <p style="color: var(--text-muted); margin-top: 0;">Add songs to this event's lineup.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Order</th>
                                <th>Title</th>
                                <th>Artist</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($songs as $song): ?>
                                <tr>
                                    <td><span style="background: var(--bg-secondary); padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 500;"><?php echo $song['sort_order']; ?></span></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="ph ph-music-note" style="color: var(--primary);"></i>
                                            <strong style="font-size: 1.05rem;"><?php echo htmlspecialchars($song['title']); ?></strong>
                                        </div>
                                    </td>
                                    <td style="color: var(--text-muted);"><?php echo htmlspecialchars($song['artist']); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn btn-sm btn-secondary" onclick='editSong(<?php echo json_encode($song); ?>)' title="Edit Song">
                                                <i class="ph ph-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary" onclick='viewLyrics(<?php echo json_encode($song); ?>)' title="View Lyrics/Chords">
                                                <i class="ph ph-book-open"></i>
                                            </button>
                                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to remove this song?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="action" value="delete_song">
                                                <input type="hidden" name="song_id" value="<?php echo $song['id']; ?>">
                                                <button type="submit" class="btn btn-sm" style="background: var(--bg-secondary); color: var(--danger); border: 1px solid var(--border-primary);" title="Remove Song"><i class="ph ph-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Song Modal -->
<div id="songModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close-modal" onclick="closeSongModal()">&times;</span>
        <h2 id="modalTitle" style="margin-top: 0;">Add Song</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" id="songAction" value="add_song">
            <input type="hidden" name="song_id" id="songId" value="">
            <input type="hidden" name="event_id" value="<?php echo $selectedEventId; ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Song Title *</label>
                    <input type="text" name="title" id="songTitle" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Artist</label>
                    <input type="text" name="artist" id="songArtist" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" id="songSort" class="form-control" value="0" style="width: 100px;">
            </div>
            
            <div class="form-group" style="position: relative;">
                <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                    Lyrics
                    <button type="button" class="btn btn-sm btn-secondary" onclick="fetchLyrics()">
                        <i class="ph ph-download-simple"></i> Auto-fetch Lyrics
                    </button>
                </label>
                <textarea name="lyrics" id="songLyrics" class="form-control" rows="6" style="font-family: monospace;"></textarea>
                <small id="fetchStatus" style="color: var(--primary); display: none;">Fetching lyrics...</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Chords</label>
                <textarea name="chords" id="songChords" class="form-control" rows="4" style="font-family: monospace;"></textarea>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="closeSongModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Song</button>
            </div>
        </form>
    </div>
</div>

<!-- View Lyrics Modal -->
<div id="lyricsModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close-modal" onclick="closeLyricsModal()">&times;</span>
        <h2 id="viewSongTitle" style="margin-top: 0; margin-bottom: 0.5rem;">Song Title</h2>
        <h4 id="viewSongArtist" style="color: var(--text-muted); font-weight: normal; margin-top: 0; margin-bottom: 1.5rem;">Artist</h4>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h3 style="border-bottom: 1px solid var(--border-primary); padding-bottom: 0.5rem; margin-top: 0;">Lyrics</h3>
                <pre id="viewLyricsText" style="white-space: pre-wrap; font-family: 'Inter', sans-serif; background: transparent; padding: 0; border: none;"></pre>
            </div>
            <div>
                <h3 style="border-bottom: 1px solid var(--border-primary); padding-bottom: 0.5rem; margin-top: 0;">Chords</h3>
                <pre id="viewChordsText" style="white-space: pre-wrap; font-family: monospace; background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius);"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function openSongModal() {
    document.getElementById('modalTitle').textContent = 'Add Song';
    document.getElementById('songAction').value = 'add_song';
    document.getElementById('songId').value = '';
    document.getElementById('songTitle').value = '';
    document.getElementById('songArtist').value = '';
    document.getElementById('songLyrics').value = '';
    document.getElementById('songChords').value = '';
    document.getElementById('songSort').value = '0';
    document.getElementById('songModal').style.display = 'block';
}

function editSong(song) {
    document.getElementById('modalTitle').textContent = 'Edit Song';
    document.getElementById('songAction').value = 'edit_song';
    document.getElementById('songId').value = song.id;
    document.getElementById('songTitle').value = song.title;
    document.getElementById('songArtist').value = song.artist || '';
    document.getElementById('songLyrics').value = song.lyrics || '';
    document.getElementById('songChords').value = song.chords || '';
    document.getElementById('songSort').value = song.sort_order || '0';
    document.getElementById('songModal').style.display = 'block';
}

function closeSongModal() {
    document.getElementById('songModal').style.display = 'none';
}

function viewLyrics(song) {
    document.getElementById('viewSongTitle').textContent = song.title;
    document.getElementById('viewSongArtist').textContent = song.artist ? 'by ' + song.artist : '';
    document.getElementById('viewLyricsText').textContent = song.lyrics || 'No lyrics available.';
    document.getElementById('viewChordsText').textContent = song.chords || 'No chords available.';
    document.getElementById('lyricsModal').style.display = 'block';
}

function closeLyricsModal() {
    document.getElementById('lyricsModal').style.display = 'none';
}

async function fetchLyrics() {
    const title = document.getElementById('songTitle').value.trim();
    const artist = document.getElementById('songArtist').value.trim();
    const status = document.getElementById('fetchStatus');
    
    if (!title) {
        alert('Please enter a song title first.');
        return;
    }
    if (!artist) {
        alert('Please enter an artist to fetch lyrics reliably.');
        return;
    }
    
    status.style.display = 'inline';
    status.textContent = 'Fetching lyrics...';
    
    try {
        const response = await fetch(`https://api.lyrics.ovh/v1/${encodeURIComponent(artist)}/${encodeURIComponent(title)}`);
        const data = await response.json();
        
        if (data.lyrics) {
            document.getElementById('songLyrics').value = data.lyrics;
            status.textContent = 'Lyrics found!';
            setTimeout(() => status.style.display = 'none', 3000);
        } else {
            status.textContent = 'Lyrics not found.';
            status.style.color = 'red';
            setTimeout(() => { status.style.display = 'none'; status.style.color = 'var(--primary)'; }, 3000);
        }
    } catch (e) {
        status.textContent = 'Error fetching lyrics.';
        status.style.color = 'red';
        setTimeout(() => { status.style.display = 'none'; status.style.color = 'var(--primary)'; }, 3000);
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('songModal')) {
        closeSongModal();
    }
    if (event.target == document.getElementById('lyricsModal')) {
        closeLyricsModal();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
