<?php
/**
 * Smart AI Assistant API Endpoint
 * AFB Mangaan Attendance System
 * Zero-API-Key Local Natural Language Query, Direct Action Engine & Navigation Router
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../functions/attendance_logic.php';
require_once __DIR__ . '/../functions/activity_logger.php';

// Verify authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$church = $_SESSION['church'] ?? 'AFB Mangaan';

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$rawQuery = trim($input['query'] ?? '');

if (empty($rawQuery)) {
    echo json_encode([
        'success' => false,
        'message' => 'Query parameter is required.'
    ]);
    exit();
}

$queryLower = strtolower($rawQuery);
$pdo = getDB();

$reply = '';
$quickActions = [];
$actionCommand = null;
$cardType = null;
$cardData = null;

// =========================================================================
// STAGE 1: DIRECT ACTIONS (TIME-IN ATTENDEE, MODAL OPENING, EXPORTS, THEME)
// =========================================================================

// ACTION: TIME-IN / RECORD ATTENDANCE FOR AN ATTENDEE
if (preg_match('/(time in|time-in|record attendance|check in|check-in|naka-attend|dumating|pumasok si)\s+(.+)/i', $queryLower, $matches)) {
    $targetName = trim($matches[2]);
    // Clean trailing keywords
    $targetName = preg_replace('/(today|now|sa service|event|for event)/i', '', $targetName);
    $targetName = trim($targetName);

    if (!empty($targetName)) {
        // Find member by name
        $stmtMember = $pdo->prepare("SELECT id, fullname, category, qr_token FROM attendees WHERE church = ? AND status = 'Active' AND fullname LIKE ? LIMIT 1");
        $stmtMember->execute([$church, "%{$targetName}%"]);
        $member = $stmtMember->fetch();

        if ($member) {
            // Find today's event or latest active/upcoming event
            $todayEvent = getTodayEvent();
            if (!$todayEvent) {
                $stmtEvent = $pdo->prepare("SELECT id, event_name, start_date FROM events WHERE church = ? ORDER BY start_date DESC, id DESC LIMIT 1");
                $stmtEvent->execute([$church]);
                $todayEvent = $stmtEvent->fetch();
            }

            if ($todayEvent) {
                $eventId = $todayEvent['id'];
                $eventName = htmlspecialchars($todayEvent['event_name']);

                // Record attendance
                $res = recordAttendance($eventId, $member['id'], 'Present', 'AI Assistant', 'Logged via AI Voice/Text Command');

                if ($res['success']) {
                    $reply = "✅ **Attendance Recorded Successfully!**\n\n";
                    $reply .= "• Member: **{$member['fullname']}** (" . ($member['category'] ?: 'Member') . ")\n";
                    $reply .= "• Event: **{$eventName}**\n";
                    $reply .= "• Status: **Present** (Recorded via AI Assistant)\n";
                    $reply .= "• Time: **" . date('g:i A') . "**\n";

                    $cardType = 'EMPLOYEE_LIST';
                    $cardData = [
                        'title' => 'Attendance Recorded',
                        'subtitle' => $eventName,
                        'items' => [[
                            'name' => $member['fullname'],
                            'badge' => $member['category'] ?: 'Member',
                            'detail' => 'Present — ' . date('g:i A')
                        ]]
                    ];

                    $quickActions = [
                        ['label' => '📋 Who is present today?', 'query' => 'Who is present today?'],
                        ['label' => '📋 Who was absent?', 'query' => 'Who was absent?']
                    ];
                } else {
                    $reply = "⚠️ Failed to record attendance for **{$member['fullname']}**. Please try again.";
                }
            } else {
                $reply = "⚠️ No active or upcoming events found for **{$church}**. Please create an event first.";
            }
        } else {
            $reply = "🔍 Member **'" . htmlspecialchars($targetName) . "'** not found in **{$church}** database. Please check the spelling or add them first.";
            $quickActions[] = ['label' => '👤 Add New Member', 'query' => 'Add new member'];
        }
    }
}

// ACTION: MARK MEMBER ABSENT
elseif (preg_match('/(mark|set)\s+(.+)\s+(as absent|absent)/i', $queryLower, $matches)) {
    $targetName = trim($matches[2]);
    $stmtMember = $pdo->prepare("SELECT id, fullname, category FROM attendees WHERE church = ? AND status = 'Active' AND fullname LIKE ? LIMIT 1");
    $stmtMember->execute([$church, "%{$targetName}%"]);
    $member = $stmtMember->fetch();

    if ($member) {
        $todayEvent = getTodayEvent();
        if (!$todayEvent) {
            $stmtEvent = $pdo->prepare("SELECT id, event_name FROM events WHERE church = ? ORDER BY start_date DESC LIMIT 1");
            $stmtEvent->execute([$church]);
            $todayEvent = $stmtEvent->fetch();
        }

        if ($todayEvent) {
            recordAttendance($todayEvent['id'], $member['id'], 'Absent', 'AI Assistant', 'Marked absent via AI Assistant');
            $reply = "📋 Marked **{$member['fullname']}** as **Absent** for **" . htmlspecialchars($todayEvent['event_name']) . "**.";
        } else {
            $reply = "No active event found to update attendance.";
        }
    } else {
        $reply = "Member '" . htmlspecialchars($targetName) . "' not found.";
    }
}

// ACTION: OPEN MODALS
elseif (preg_match('/(add member|new member|create member|mag-add ng member)/i', $queryLower)) {
    $reply = "👤 Opening **Add New Member** form...";
    $actionCommand = ['type' => 'OPEN_MODAL', 'modal' => 'add_member', 'fallback_url' => 'members.php?action=add'];
}
elseif (preg_match('/(create event|add event|new event|mag-create ng event)/i', $queryLower)) {
    $reply = "📅 Opening **Create Event** form...";
    $actionCommand = ['type' => 'OPEN_MODAL', 'modal' => 'create_event', 'fallback_url' => 'events.php?action=create'];
}

// ACTION: THEME & EXPORT COMMANDS
elseif (preg_match('/(theme|dark mode|light mode|toggle theme|switch theme)/i', $queryLower)) {
    $reply = "🎨 Toggling system theme mode...";
    $actionCommand = ['type' => 'TOGGLE_THEME'];
}
elseif (preg_match('/(export members|download members|download excel|export report)/i', $queryLower)) {
    $reply = "📥 Exporting member records file...";
    $actionCommand = ['type' => 'EXPORT', 'url' => 'api/export_members.php'];
}

// =========================================================================
// STAGE 2: SPECIFIC DATA QUERIES & LOOKUPS
// =========================================================================

// INTENT: SINGLE MEMBER PROFILE LOOKUP / SEARCH
elseif (preg_match('/(who is|search|find|lookup|detalye ni|profile of|info of)\s+(.+)/i', $queryLower, $matches) && !preg_match('/(log|event|present|absent|category)/i', $queryLower)) {
    $searchTerm = trim($matches[2]);
    $searchTermClean = preg_replace('/(member|profile|contact|details|info)/i', '', $searchTerm);
    $searchTermClean = trim($searchTermClean);

    if (!empty($searchTermClean)) {
        $stmt = $pdo->prepare("
            SELECT id, fullname, category, contact, email, status, qr_token, created_at
            FROM attendees
            WHERE church = ? AND (fullname LIKE ? OR qr_token LIKE ? OR contact LIKE ?)
            ORDER BY fullname ASC LIMIT 5
        ");
        $searchPattern = "%{$searchTermClean}%";
        $stmt->execute([$church, $searchPattern, $searchPattern, $searchPattern]);
        $results = $stmt->fetchAll();

        if (!empty($results)) {
            $count = count($results);
            $reply = "🔍 **Member Search Results for '" . htmlspecialchars($searchTermClean) . "'** ({$count} found):\n\n";

            $cardItems = [];
            foreach ($results as $m) {
                $contact = $m['contact'] ? "📞 {$m['contact']}" : "No contact";
                $email = $m['email'] ? " ✉️ {$m['email']}" : "";
                $reply .= "• **{$m['fullname']}** (" . ($m['category'] ?: 'Unassigned') . ")\n  {$contact}{$email} | Token: `{$m['qr_token']}`\n\n";

                $cardItems[] = [
                    'name' => $m['fullname'],
                    'badge' => $m['category'] ?: 'Member',
                    'detail' => $contact . " | Token: {$m['qr_token']}"
                ];
            }

            $cardType = 'EMPLOYEE_LIST';
            $cardData = [
                'title' => "Member Lookup Results",
                'subtitle' => $searchTermClean,
                'items' => $cardItems
            ];

            $quickActions[] = ['label' => "⏱️ Time in " . $results[0]['fullname'], 'query' => "Time in " . $results[0]['fullname']];
            $quickActions[] = ['label' => '📋 Show all members', 'query' => 'Take me to Members'];
        } else {
            $reply = "🔍 No members found matching **'" . htmlspecialchars($searchTermClean) . "'** in **{$church}**.";
            $quickActions[] = ['label' => '👤 Add New Member', 'query' => 'Add new member'];
        }
    }
}

// INTENT: CATEGORY ROSTER QUERY (MEMBERS IN A SPECIFIC MINISTRY)
elseif (preg_match('/(members in|who is in|sino ang mga|roster of|list of)\s+(wmo|mcyo|ccmo|kids|visitors|men|women|youth)/i', $queryLower, $matches)) {
    $categoryReq = strtoupper(trim($matches[2]));
    // Standardize category name
    if (in_array($categoryReq, ['MEN', 'WOMEN', 'YOUTH'])) {
        if ($categoryReq === 'WOMEN') $categoryReq = 'WMO';
        if ($categoryReq === 'YOUTH') $categoryReq = 'MCYO';
        if ($categoryReq === 'MEN') $categoryReq = 'CCMO';
    }

    $stmt = $pdo->prepare("
        SELECT fullname, contact, email, qr_token
        FROM attendees
        WHERE church = ? AND status = 'Active' AND category LIKE ?
        ORDER BY fullname ASC
    ");
    $stmt->execute([$church, "%{$categoryReq}%"]);
    $membersList = $stmt->fetchAll();

    if (!empty($membersList)) {
        $count = count($membersList);
        $reply = "👥 **{$categoryReq} Ministry Member Roster ({$church})**\n";
        $reply .= "Total Active: **{$count}** members\n\n";

        $cardItems = [];
        foreach ($membersList as $m) {
            $contact = $m['contact'] ? "📞 {$m['contact']}" : "No contact";
            $reply .= "• **{$m['fullname']}** — {$contact}\n";

            $cardItems[] = [
                'name' => $m['fullname'],
                'badge' => $categoryReq,
                'detail' => $contact
            ];
        }

        $cardType = 'EMPLOYEE_LIST';
        $cardData = [
            'title' => "{$categoryReq} Roster ({$count})",
            'subtitle' => $church,
            'items' => $cardItems
        ];

        $quickActions[] = ['label' => '📊 Ministry Breakdown', 'query' => 'Category breakdown'];
        $quickActions[] = ['label' => '📋 Who was absent?', 'query' => 'Who was absent?'];
    } else {
        $reply = "No active members found registered under **{$categoryReq}** ministry in **{$church}**.";
    }
}

// INTENT: STAT OVERVIEW / SUMMARY
elseif (preg_match('/(summary|overview|status|attendance rate|total members|stats|metrics|ilan ang tao|ilan ang active)/i', $queryLower)) {
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM attendees WHERE church = ? AND status = 'Active'");
    $stmtTotal->execute([$church]);
    $totalMembers = (int)($stmtTotal->fetch()['total'] ?? 0);

    $stmtEvent = $pdo->prepare("SELECT id, event_name, start_date FROM events WHERE church = ? ORDER BY start_date DESC, id DESC LIMIT 1");
    $stmtEvent->execute([$church]);
    $latestEvent = $stmtEvent->fetch();

    $presentCount = 0;
    $eventName = $latestEvent ? htmlspecialchars($latestEvent['event_name']) : 'Latest Event';

    if ($latestEvent) {
        $stmtPres = $pdo->prepare("SELECT COUNT(DISTINCT attendee_id) as present FROM attendance_logs WHERE event_id = ? AND status = 'Present'");
        $stmtPres->execute([$latestEvent['id']]);
        $presentCount = (int)($stmtPres->fetch()['present'] ?? 0);
    }

    $absentCount = max(0, $totalMembers - $presentCount);
    $rate = $totalMembers > 0 ? round(($presentCount / $totalMembers) * 100, 1) : 0;

    $reply = "📊 **AFB Mangaan Attendance & Ministry Summary**\n\n";
    $reply .= "• Total Active Members: **{$totalMembers}**\n";
    $reply .= "• Latest Event: **{$eventName}**\n";
    $reply .= "• Present: **{$presentCount}** | Absent: **{$absentCount}**\n";
    $reply .= "• Attendance Rate: **{$rate}%**\n";

    $cardType = 'STAT_SUMMARY';
    $cardData = [
        'total_members' => $totalMembers,
        'event_name' => $eventName,
        'present' => $presentCount,
        'absent' => $absentCount,
        'rate' => $rate
    ];

    $quickActions = [
        ['label' => '📋 Who was absent?', 'query' => 'Who was absent?'],
        ['label' => '⚠️ At-Risk Members', 'query' => 'Show at-risk members'],
        ['label' => '📊 Ministry Breakdown', 'query' => 'Category breakdown']
    ];
}

// INTENT: PRESENT MEMBERS TODAY
elseif (preg_match('/(present|pumasok|dumating|naka-attend|who attended|list of present|attendance today|attending|sino ang pumasok)/i', $queryLower)) {
    $stmt = $pdo->prepare("SELECT id, event_name, start_date FROM events WHERE church = ? ORDER BY start_date DESC, id DESC LIMIT 1");
    $stmt->execute([$church]);
    $latestEvent = $stmt->fetch();

    if ($latestEvent) {
        $eventId = $latestEvent['id'];
        $eventName = htmlspecialchars($latestEvent['event_name']);
        $eventDate = date('M d, Y', strtotime($latestEvent['start_date']));

        $stmt = $pdo->prepare("
            SELECT a.fullname, a.category, a.contact, al.log_time, al.method
            FROM attendance_logs al
            JOIN attendees a ON al.attendee_id = a.id
            WHERE al.event_id = ? AND al.status = 'Present' AND a.church = ?
            ORDER BY al.log_time ASC
        ");
        $stmt->execute([$eventId, $church]);
        $presentList = $stmt->fetchAll();

        if (!empty($presentList)) {
            $count = count($presentList);
            $reply = "✅ **Present Members for {$eventName} ({$eventDate})**\n";
            $reply .= "Total Present: **{$count}** members\n\n";

            $cardItems = [];
            foreach ($presentList as $m) {
                $category = $m['category'] ? "({$m['category']})" : "";
                $time = $m['log_time'] ? date('g:i A', strtotime($m['log_time'])) : '';
                $method = $m['method'] ? " [{$m['method']}]" : "";
                $reply .= "• **{$m['fullname']}** {$category}{$method} — {$time}\n";

                $cardItems[] = [
                    'name' => $m['fullname'],
                    'badge' => $m['category'] ?: 'Member',
                    'detail' => $time . ($m['method'] ? " ({$m['method']})" : "")
                ];
            }

            $cardType = 'EMPLOYEE_LIST';
            $cardData = [
                'title' => "Present Members ({$count})",
                'subtitle' => $eventName,
                'items' => $cardItems
            ];

            $quickActions[] = ['label' => '📋 Who was absent?', 'query' => 'Who was absent?'];
            $quickActions[] = ['label' => '📊 Ministry Breakdown', 'query' => 'Category breakdown'];
        } else {
            $reply = "ℹ️ No present attendance records logged yet for **{$eventName} ({$eventDate})**.";
        }
    } else {
        $reply = "No events found for **{$church}**.";
    }
}

// INTENT: ABSENT MEMBERS
elseif (preg_match('/(absent|kulang|missing|di pumasok|hindi um-attend|didn\'t attend|sino ang hindi)/i', $queryLower)) {
    $stmt = $pdo->prepare("SELECT id, event_name, start_date FROM events WHERE church = ? ORDER BY start_date DESC, id DESC LIMIT 1");
    $stmt->execute([$church]);
    $latestEvent = $stmt->fetch();

    if ($latestEvent) {
        $eventId = $latestEvent['id'];
        $eventName = htmlspecialchars($latestEvent['event_name']);
        $eventDate = date('M d, Y', strtotime($latestEvent['start_date']));

        $stmt = $pdo->prepare("
            SELECT a.fullname, a.category, a.contact, a.email, a.qr_token
            FROM attendees a
            LEFT JOIN attendance_logs al ON a.id = al.attendee_id AND al.event_id = ?
            WHERE a.church = ? AND a.status = 'Active' AND (al.status = 'Absent' OR al.id IS NULL)
            ORDER BY a.fullname ASC
        ");
        $stmt->execute([$eventId, $church]);
        $absentees = $stmt->fetchAll();

        if (!empty($absentees)) {
            $count = count($absentees);
            $reply = "📋 **Absent Members for {$eventName} ({$eventDate})**\n";
            $reply .= "Found **{$count}** absent/unrecorded members for follow-up:\n\n";

            $cardItems = [];
            foreach ($absentees as $m) {
                $category = $m['category'] ? "({$m['category']})" : "";
                $contact = $m['contact'] ? "📞 {$m['contact']}" : "No contact";
                $reply .= "• **{$m['fullname']}** {$category} — {$contact}\n";

                $cardItems[] = [
                    'name' => $m['fullname'],
                    'badge' => $m['category'] ?: 'Member',
                    'detail' => $contact
                ];
            }

            $cardType = 'EMPLOYEE_LIST';
            $cardData = [
                'title' => "Absent Members ({$count})",
                'subtitle' => $eventName,
                'items' => $cardItems
            ];

            $quickActions[] = ['label' => '✍️ Draft Announcement', 'query' => 'Draft announcement'];
            $quickActions[] = ['label' => '⚠️ Show At-Risk Members', 'query' => 'Show at-risk members'];
        } else {
            $reply = "🎉 **Great news!** Perfect attendance recorded for **{$eventName} ({$eventDate})**! All active members were present.";
        }
    } else {
        $reply = "No events found in the database for **{$church}**. Please create an event first.";
    }
}

// INTENT: AT-RISK MEMBERS
elseif (preg_match('/(at-risk|at risk|risk|danger|inactive|matagal|absenteeism|low attendance)/i', $queryLower)) {
    $stats = getRetentionStats(3);
    $atRisk = $stats['at_risk'] ?? [];

    if (!empty($atRisk)) {
        $count = count($atRisk);
        $reply = "⚠️ **At-Risk Member Alert (Low Attendance Rate)**\n";
        $reply .= "Identified **{$count}** members with low attendance (<30% rate over past 3 months):\n\n";

        $cardItems = [];
        foreach ($atRisk as $m) {
            $rate = $m['attendance_rate'] ?? 0;
            $reply .= "• **{$m['fullname']}** ({$m['category']}) — Attendance Rate: **{$rate}%** ({$m['attended_events']}/{$m['total_events']} events)\n";

            $cardItems[] = [
                'name' => $m['fullname'],
                'badge' => $m['category'] ?: 'Member',
                'detail' => "Rate: {$rate}% ({$m['attended_events']}/{$m['total_events']} events)"
            ];
        }

        $cardType = 'EMPLOYEE_LIST';
        $cardData = [
            'title' => "At-Risk Members ({$count})",
            'subtitle' => 'Low Attendance (<30%)',
            'items' => $cardItems
        ];

        $quickActions[] = ['label' => '📋 Who was absent last event?', 'query' => 'Who was absent?'];
        $quickActions[] = ['label' => '📊 Category Summary', 'query' => 'Category breakdown'];
    } else {
        $reply = "✅ **Excellent retention!** No active members are currently flagged as at-risk in **{$church}**.";
    }
}

// INTENT: CATEGORY BREAKDOWN OVERVIEW
elseif (preg_match('/(category|categories|breakdown|ministry breakdown|groups)/i', $queryLower)) {
    $stmt = $pdo->prepare("
        SELECT category, COUNT(*) as count 
        FROM attendees 
        WHERE church = ? AND status = 'Active' 
        GROUP BY category 
        ORDER BY count DESC
    ");
    $stmt->execute([$church]);
    $categories = $stmt->fetchAll();

    $stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM attendees WHERE church = ? AND status = 'Active'");
    $stmtTotal->execute([$church]);
    $totalMembers = $stmtTotal->fetch()['total'] ?? 0;

    $reply = "📊 **Member Category Breakdown for {$church}**\n";
    $reply .= "Total Active Members: **{$totalMembers}**\n\n";

    if (!empty($categories)) {
        foreach ($categories as $c) {
            $catName = $c['category'] ?: 'Unassigned';
            $pct = $totalMembers > 0 ? round(($c['count'] / $totalMembers) * 100, 1) : 0;
            $reply .= "• **{$catName}**: {$c['count']} members ({$pct}%)\n";
        }
    } else {
        $reply .= "No category records found.";
    }

    $quickActions[] = ['label' => '👥 Who is in WMO?', 'query' => 'Who is in WMO?'];
    $quickActions[] = ['label' => '⚠️ At-Risk Members', 'query' => 'Show at-risk members'];
}

// INTENT: EVENTS SCHEDULE
elseif (preg_match('/(upcoming events|events list|schedule|service schedule|meeting|kailan ang service|may event)/i', $queryLower)) {
    $stmt = $pdo->prepare("SELECT event_name, start_date, event_time, type, status, location FROM events WHERE church = ? ORDER BY start_date DESC LIMIT 5");
    $stmt->execute([$church]);
    $events = $stmt->fetchAll();

    if (!empty($events)) {
        $reply = "📅 **Recent & Upcoming Events ({$church})**\n\n";
        foreach ($events as $e) {
            $date = date('M d, Y', strtotime($e['start_date']));
            $time = $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : '';
            $statusBadge = strtoupper($e['status']);
            $reply .= "• **{$e['event_name']}** [{$statusBadge}]\n  {$date} {$time} — {$e['type']} ({$e['location']})\n\n";
        }
        $quickActions[] = ['label' => '📅 Create New Event', 'query' => 'Create event'];
        $quickActions[] = ['label' => '📋 Who was absent?', 'query' => 'Who was absent?'];
    } else {
        $reply = "No events registered for **{$church}** yet.";
    }
}

// INTENT: DRAFT ANNOUNCEMENT
elseif (preg_match('/(draft|description|announcement|text|invitation|promote|post)/i', $queryLower)) {
    $todayEvent = getTodayEvent();
    $eventName = $todayEvent['event_name'] ?? 'Sunday Worship Service';
    $eventTime = $todayEvent['event_time'] ? date('g:i A', strtotime($todayEvent['event_time'])) : '9:00 AM';

    $reply = "✍️ **Draft Event Announcement Template**\n\n";
    $reply .= "Glorious greetings in Christ, **{$church}** family! 🙏\n\n";
    $reply .= "We invite everyone to join us for our **{$eventName}**!\n";
    $reply .= "🗓️ **Date**: " . date('F d, Y') . "\n";
    $reply .= "⏰ **Time**: {$eventTime}\n";
    $reply .= "📍 **Location**: Main Sanctuary, {$church}\n\n";
    $reply .= "Come with an expectant heart for a blessed time of worship, prayer, and God's Word. See you there and God bless you richly!";

    $quickActions[] = ['label' => '📋 Copy Announcement', 'query' => 'Draft announcement'];
}

// INTENT: SYSTEM LOGS QUERY
elseif (preg_match('/(system logs|activity logs|security logs|audit trail|recent activity)/i', $queryLower)) {
    $recentActivity = getRecentActivity(5);

    if (!empty($recentActivity)) {
        $reply = "📜 **Recent System Audit Logs ({$church})**\n\n";
        foreach ($recentActivity as $log) {
            $userName = htmlspecialchars($log['user_name'] ?? 'System');
            $time = date('M d, g:i A', strtotime($log['timestamp']));
            $reply .= "• **[{$log['action']}]** by {$userName} ({$time})\n  _{$log['details']}_\n\n";
        }
    } else {
        $reply = "No recent system logs recorded.";
    }

    $quickActions[] = ['label' => '📋 Go to System Logs', 'query' => 'Go to System Logs'];
}

// =========================================================================
// STAGE 3: EXPLICIT PAGE NAVIGATION (ALL 9 SYSTEM PAGES MAPPED)
// =========================================================================
elseif (preg_match('/(attendance audit|audit history)/i', $queryLower) && preg_match('/(go|open|navigate|punta|pakita|show|view|page)/i', $queryLower)) {
    $reply = "📋 Navigating to **Attendance Audit**...";
    $actionCommand = ['type' => 'NAVIGATE', 'url' => 'attendance_audit.php'];
}
elseif (preg_match('/(attendance|check in|check-in|time in)/i', $queryLower) && preg_match('/(go|open|navigate|punta|pakita|show|view|page)/i', $queryLower)) {
    $reply = "✅ Navigating to **Record Attendance** page...";
    $actionCommand = ['type' => 'NAVIGATE', 'url' => 'attendance.php'];
}
elseif (preg_match('/(dashboard|home)/i', $queryLower) && preg_match('/(go|open|navigate|punta|pakita|show|view|page)/i', $queryLower)) {
    $reply = "🚀 Navigating to **Dashboard**...";
    $actionCommand = ['type' => 'NAVIGATE', 'url' => 'dashboard.php'];
}
elseif (preg_match('/(members|member)/i', $queryLower) && preg_match('/(go|open|navigate|punta|pakita|show|view|page|list)/i', $queryLower) && !preg_match('/(add|create|new)/i', $queryLower)) {
    $reply = "👥 Navigating to **Members List**...";
    $actionCommand = ['type' => 'NAVIGATE', 'url' => 'members.php'];
}
elseif (preg_match('/(events|event|schedule|services)/i', $queryLower) && preg_match('/(go|open|navigate|punta|pakita|show|view|page)/i', $queryLower) && !preg_match('/(create|add|new|draft)/i', $queryLower)) {
    $reply = "📅 Navigating to **Events Schedule**...";
    $actionCommand = ['type' => 'NAVIGATE', 'url' => 'events.php'];
}
elseif (preg_match('/(reports|report|analytics)/i', $queryLower) && preg_match('/(go|open|navigate|punta|pakita|show|view|page)/i', $queryLower)) {
    $reply = "📊 Navigating to **Reports & Analytics**...";
    $actionCommand = ['type' => 'NAVIGATE', 'url' => 'reports.php'];
}
elseif (preg_match('/(logs|log|activity)/i', $queryLower) && preg_match('/(go|open|navigate|punta|pakita|show|view|page)/i', $queryLower)) {
    $reply = "📜 Navigating to **System Logs**...";
    $actionCommand = ['type' => 'NAVIGATE', 'url' => 'logs.php'];
}
elseif (preg_match('/(change password|password)/i', $queryLower) && preg_match('/(go|open|navigate|punta|pakita|show|view|page)/i', $queryLower)) {
    $reply = "🔒 Navigating to **Change Password** page...";
    $actionCommand = ['type' => 'NAVIGATE', 'url' => 'change_password.php'];
}
elseif (preg_match('/(settings|setting|profile)/i', $queryLower) && preg_match('/(go|open|navigate|punta|pakita|show|view|page)/i', $queryLower)) {
    $reply = "⚙️ Navigating to **Settings**...";
    $actionCommand = ['type' => 'NAVIGATE', 'url' => 'settings.php'];
}

// Fallback for generic navigation (e.g., "Go to attendance")
elseif (preg_match('/(go to|open|navigate to|punta sa)\s+(.+)/i', $queryLower, $matches)) {
    $target = trim($matches[2]);
    if (strpos($target, 'attend') !== false) {
        $reply = "✅ Navigating to **Record Attendance** page...";
        $actionCommand = ['type' => 'NAVIGATE', 'url' => 'attendance.php'];
    } elseif (strpos($target, 'member') !== false) {
        $reply = "👥 Navigating to **Members List**...";
        $actionCommand = ['type' => 'NAVIGATE', 'url' => 'members.php'];
    } elseif (strpos($target, 'event') !== false) {
        $reply = "📅 Navigating to **Events Schedule**...";
        $actionCommand = ['type' => 'NAVIGATE', 'url' => 'events.php'];
    } elseif (strpos($target, 'report') !== false) {
        $reply = "📊 Navigating to **Reports & Analytics**...";
        $actionCommand = ['type' => 'NAVIGATE', 'url' => 'reports.php'];
    } elseif (strpos($target, 'log') !== false) {
        $reply = "📜 Navigating to **System Logs**...";
        $actionCommand = ['type' => 'NAVIGATE', 'url' => 'logs.php'];
    } elseif (strpos($target, 'dash') !== false || strpos($target, 'home') !== false) {
        $reply = "🚀 Navigating to **Dashboard**...";
        $actionCommand = ['type' => 'NAVIGATE', 'url' => 'dashboard.php'];
    } else {
        $reply = "⚙️ Navigating to **Settings**...";
        $actionCommand = ['type' => 'NAVIGATE', 'url' => 'settings.php'];
    }
}

// =========================================================================
// FALLBACK / GENERAL ASSISTANT HELP
// =========================================================================
else {
    $reply = "👋 Hello **" . htmlspecialchars($_SESSION['fullname'] ?? 'Admin') . "**! I am your **AFB Mangaan Smart AI Assistant**.\n\n";
    $reply .= "I can execute actions, search records, and navigate pages for you. Try asking:\n\n";
    $reply .= "• *'Time in Juan Dela Cruz'*\n";
    $reply .= "• *'Who is Maria?'* (Search member profile)\n";
    $reply .= "• *'Who belongs to WMO?'* (List ministry roster)\n";
    $reply .= "• *'Go to Attendance'* (or *'Go to Members'*)\n";

    $quickActions = [
        ['label' => '✅ Go to Attendance', 'query' => 'Go to Attendance'],
        ['label' => '📊 Show Overview', 'query' => 'Show summary'],
        ['label' => '📋 Absent Members List', 'query' => 'Who was absent?'],
        ['label' => '👥 Who is in WMO?', 'query' => 'Who is in WMO?']
    ];
}

echo json_encode([
    'success' => true,
    'reply' => $reply,
    'quick_actions' => $quickActions,
    'action_command' => $actionCommand,
    'card_type' => $cardType,
    'card_data' => $cardData
]);
