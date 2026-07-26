/**
 * Dual-Mode AI Assistant & Group Chat Widget JavaScript Engine
 * AFB Mangaan Attendance System
 * Supports: Local AI NLP Query Processor, Web Speech API (STT & TTS), Group Chat Messenger & Emoji Reactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // UI Elements
    const fabBtn = document.getElementById('aiFabToggle');
    const fabIcon = document.getElementById('aiFabIcon');
    const drawer = document.getElementById('aiAssistantDrawer');
    const closeBtn = document.getElementById('aiCloseDrawer');
    const clearBtn = document.getElementById('aiClearChat');
    const unreadBadge = document.getElementById('aiFabUnreadBadge');

    // Tab Selector Buttons
    const tabAiBtn = document.getElementById('tabAiBtn');
    const tabGroupBtn = document.getElementById('tabGroupBtn');
    const viewAiContent = document.getElementById('viewAiContent');
    const viewGroupContent = document.getElementById('viewGroupContent');

    // AI Assistant Elements
    const aiChatForm = document.getElementById('aiChatForm');
    const aiQueryInput = document.getElementById('aiQueryInput');
    const aiMessagesContainer = document.getElementById('aiChatMessages');
    const aiMicToggle = document.getElementById('aiMicToggle');
    const aiSpeakerToggle = document.getElementById('aiSpeakerToggle');

    // Group Chat Elements
    const groupRoomListView = document.getElementById('groupRoomListView');
    const groupActiveChatView = document.getElementById('groupActiveChatView');
    const roomsContainer = document.getElementById('groupRoomsContainer');
    const roomSearchInput = document.getElementById('groupRoomSearchInput');
    const btnOpenCreateGroup = document.getElementById('btnOpenCreateGroup');
    const createGroupModal = document.getElementById('createGroupModal');
    const createGroupForm = document.getElementById('createGroupForm');
    const createGroupInput = document.getElementById('createGroupNameInput');
    const btnCloseCreateGroup = document.getElementById('btnCloseCreateGroup');

    // Active Room State
    const activeRoomTitle = document.getElementById('activeRoomTitle');
    const btnBackToRooms = document.getElementById('btnBackToRooms');
    const groupMessagesContainer = document.getElementById('groupMessagesContainer');
    const groupChatForm = document.getElementById('groupChatForm');
    const groupMessageInput = document.getElementById('groupMessageInput');
    const replyPreviewBar = document.getElementById('replyPreviewBar');
    const replySenderName = document.getElementById('replySenderName');
    const replySnippetText = document.getElementById('replySnippetText');
    const btnCancelReply = document.getElementById('btnCancelReply');

    // State Variables
    let activeTab = 'ai'; // 'ai' | 'group'
    let currentRoomId = null;
    let isSpeechSynthesisEnabled = false;
    let recognitionInstance = null;
    let isListening = false;
    let pollingInterval = null;
    let activeReplyToId = null;

    // =========================================================================
    // WEB SPEECH API RECOGNITION (STT) & SYNTHESIS (TTS)
    // =========================================================================
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    function initSpeechRecognition() {
        if (!SpeechRecognition) return null;

        try {
            const rec = new SpeechRecognition();
            rec.continuous = false;
            rec.interimResults = true; // Show live transcription in input box
            rec.maxAlternatives = 1;
            rec.lang = 'en-US';

            rec.onstart = function () {
                isListening = true;
                if (aiMicToggle) {
                    aiMicToggle.classList.add('mic-active');
                    aiMicToggle.style.color = '#ef4444';
                    aiMicToggle.style.background = 'rgba(239, 68, 68, 0.25)';
                    aiMicToggle.style.borderColor = '#ef4444';
                    aiMicToggle.style.boxShadow = '0 0 14px rgba(239, 68, 68, 0.6)';
                }
                if (aiQueryInput) {
                    aiQueryInput.value = '';
                    aiQueryInput.placeholder = '🎙️ Listening... Speak your query now';
                }
            };

            rec.onresult = function (event) {
                let interimTranscript = '';
                let finalTranscript = '';

                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    const text = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        finalTranscript += text;
                    } else {
                        interimTranscript += text;
                    }
                }

                if (aiQueryInput) {
                    if (interimTranscript) {
                        aiQueryInput.value = interimTranscript;
                    }
                    if (finalTranscript) {
                        aiQueryInput.value = finalTranscript;
                    }
                }

                if (finalTranscript.trim()) {
                    stopListening();
                    sendAiQuery(finalTranscript.trim());
                }
            };

            rec.onerror = function (event) {
                console.warn('Speech recognition error event:', event.error);
                stopListening();

                if (event.error === 'no-speech') {
                    // Silently reset mic state without polluting chat history
                    if (aiQueryInput) {
                        aiQueryInput.placeholder = 'No speech detected. Click 🎙️ to try again';
                    }
                    return;
                }

                let errorNoticeHtml = '';
                switch (event.error) {
                    case 'not-allowed':
                    case 'service-not-allowed':
                        errorNoticeHtml = '🎙️ <strong>Microphone access blocked.</strong> Please click the mic icon in your browser address bar to allow microphone access.';
                        break;
                    case 'network':
                        errorNoticeHtml = '🎙️ <strong>Network issue.</strong> Speech recognition requires an active internet connection.';
                        break;
                    case 'audio-capture':
                        errorNoticeHtml = '🎙️ <strong>No microphone detected.</strong> Please connect a microphone device.';
                        break;
                    default:
                        errorNoticeHtml = `🎙️ <strong>Speech recognition error:</strong> ${event.error}`;
                        break;
                }

                showBotNotice(errorNoticeHtml, 'warning');
            };

            rec.onend = function () {
                stopListening();
            };

            return rec;
        } catch (err) {
            console.error('SpeechRecognition init error:', err);
            return null;
        }
    }

    recognitionInstance = initSpeechRecognition();

    function toggleListening() {
        if (!SpeechRecognition) {
            showBotNotice('🎙️ <strong>Speech Recognition Unsupported:</strong> Web Speech API is not supported in your current browser. Please use Google Chrome, Microsoft Edge, or Safari.', 'warning');
            return;
        }

        if (isListening) {
            if (recognitionInstance) {
                try { recognitionInstance.stop(); } catch (e) { }
            }
            stopListening();
        } else {
            // Re-initialize recognition instance to ensure fresh clean state
            recognitionInstance = initSpeechRecognition();
            if (recognitionInstance) {
                try {
                    recognitionInstance.start();
                } catch (ex) {
                    console.warn('Recognition start exception:', ex);
                    stopListening();
                }
            }
        }
    }

    function stopListening() {
        isListening = false;
        if (aiMicToggle) {
            aiMicToggle.classList.remove('mic-active');
            aiMicToggle.style.color = '';
            aiMicToggle.style.background = '';
            aiMicToggle.style.borderColor = '';
            aiMicToggle.style.boxShadow = '';
        }
        if (aiQueryInput && aiQueryInput.placeholder.includes('Listening')) {
            aiQueryInput.placeholder = 'Ask AI or speak...';
        }
    }

    if (aiMicToggle) {
        aiMicToggle.addEventListener('click', toggleListening);
    }

    // Text-to-Speech (TTS)
    if (aiSpeakerToggle) {
        aiSpeakerToggle.addEventListener('click', function () {
            isSpeechSynthesisEnabled = !isSpeechSynthesisEnabled;
            if (isSpeechSynthesisEnabled) {
                aiSpeakerToggle.style.color = '#10b981';
                aiSpeakerToggle.style.borderColor = '#10b981';
                aiSpeakerToggle.style.background = 'rgba(16, 185, 129, 0.15)';
                speakText('Voice synthesis activated. I will read response messages aloud.');
            } else {
                aiSpeakerToggle.style.color = '';
                aiSpeakerToggle.style.borderColor = '';
                aiSpeakerToggle.style.background = '';
                if ('speechSynthesis' in window) window.speechSynthesis.cancel();
            }
        });
    }

    function speakText(text) {
        if (!isSpeechSynthesisEnabled || !('speechSynthesis' in window)) return;

        window.speechSynthesis.cancel(); // Stop any previous speech
        const cleanText = text.replace(/[*_#`~]/g, ''); // Strip markdown tokens
        const utterance = new SpeechSynthesisUtterance(cleanText);

        utterance.rate = 1.0;
        utterance.pitch = 1.0;

        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            const englishVoice = voices.find(v => v.lang.startsWith('en') && v.name.includes('Natural')) ||
                voices.find(v => v.lang.startsWith('en'));
            if (englishVoice) utterance.voice = englishVoice;
        }

        window.speechSynthesis.speak(utterance);
    }

    // Display Bot System Notice
    function showBotNotice(noticeHtml, type = 'info') {
        if (!aiMessagesContainer) return;
        const noticeDiv = document.createElement('div');
        noticeDiv.className = `ai-msg bot-msg system-notice-${type}`;
        noticeDiv.style.cssText = 'display: flex; gap: 0.65rem; align-items: flex-start; margin-bottom: 0.4rem;';

        const isWarning = type === 'warning';
        const iconClass = isWarning ? 'ph ph-warning-circle' : 'ph ph-info';
        const bgIconColor = isWarning ? 'rgba(239, 68, 68, 0.2)' : 'rgba(99, 102, 241, 0.2)';
        const textIconColor = isWarning ? '#ef4444' : '#818cf8';
        const bgBoxColor = isWarning ? 'rgba(239, 68, 68, 0.1)' : 'var(--bg-tertiary, #2b2b40)';
        const borderBoxColor = isWarning ? 'rgba(239, 68, 68, 0.3)' : 'var(--border-primary, #3b3b55)';

        noticeDiv.innerHTML = `
            <div style="width: 30px; height: 30px; border-radius: 8px; background: ${bgIconColor}; color: ${textIconColor}; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;">
                <i class="${iconClass}"></i>
            </div>
            <div style="background: ${bgBoxColor}; border: 1px solid ${borderBoxColor}; padding: 0.75rem 0.95rem; border-radius: 0 14px 14px 14px; font-size: 0.8rem; line-height: 1.4; color: var(--text-primary); max-width: 85%;">
                ${noticeHtml}
            </div>
        `;
        aiMessagesContainer.appendChild(noticeDiv);
        aiMessagesContainer.scrollTop = aiMessagesContainer.scrollHeight;
    }

    // Toggle Main Widget Drawer
    function toggleDrawer() {
        const isOpen = drawer.style.display === 'flex';
        if (isOpen) {
            closeDrawer();
        } else {
            drawer.style.display = 'flex';
            if (fabIcon) fabIcon.className = 'ph ph-x';
            if (activeTab === 'ai' && aiQueryInput) {
                setTimeout(() => aiQueryInput.focus(), 100);
            } else if (activeTab === 'group') {
                loadGroupRooms();
                startPolling();
            }
        }
    }

    function closeDrawer() {
        drawer.style.display = 'none';
        if (fabIcon) fabIcon.className = 'ph ph-sparkle';
        stopPolling();
        stopListening();
    }

    if (fabBtn) fabBtn.addEventListener('click', toggleDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

    // Tab Switching Logic
    if (tabAiBtn && tabGroupBtn) {
        tabAiBtn.addEventListener('click', function () {
            activeTab = 'ai';
            tabAiBtn.classList.add('active');
            tabGroupBtn.classList.remove('active');
            tabAiBtn.style.borderBottom = '2px solid #818cf8';
            tabAiBtn.style.opacity = '1';
            tabGroupBtn.style.borderBottom = 'none';
            tabGroupBtn.style.opacity = '0.7';

            viewAiContent.style.display = 'flex';
            viewGroupContent.style.display = 'none';
            stopPolling();
        });

        tabGroupBtn.addEventListener('click', function () {
            activeTab = 'group';
            tabGroupBtn.classList.add('active');
            tabAiBtn.classList.remove('active');
            tabGroupBtn.style.borderBottom = '2px solid #818cf8';
            tabGroupBtn.style.opacity = '1';
            tabAiBtn.style.borderBottom = 'none';
            tabAiBtn.style.opacity = '0.7';

            viewAiContent.style.display = 'none';
            viewGroupContent.style.display = 'flex';

            loadGroupRooms();
            startPolling();
        });
    }

    // Clear AI Chat
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            aiMessagesContainer.innerHTML = `
                <div class="ai-msg bot-msg" style="display: flex; gap: 0.65rem; align-items: flex-start;">
                    <div style="width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                        <i class="ph ph-sparkle"></i>
                    </div>
                    <div style="background: var(--bg-tertiary, #2b2b40); padding: 0.8rem 1rem; border-radius: 0 14px 14px 14px; font-size: 0.85rem; line-height: 1.5; color: var(--text-primary); max-width: 85%;">
                        Chat cleared. How can I help you today?
                    </div>
                </div>
            `;
        });
    }

    // AI Query Processing
    window.sendAiQuery = function (queryText) {
        if (!queryText) return;

        // Append User Message
        const userMsgDiv = document.createElement('div');
        userMsgDiv.className = 'ai-msg user-msg';
        userMsgDiv.style.cssText = 'display: flex; justify-content: flex-end; margin-bottom: 0.4rem;';
        userMsgDiv.innerHTML = `
            <div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 0.65rem 0.95rem; border-radius: 14px 14px 0 14px; font-size: 0.85rem; max-width: 80%;">
                ${escapeHtml(queryText)}
            </div>
        `;
        aiMessagesContainer.appendChild(userMsgDiv);
        aiMessagesContainer.scrollTop = aiMessagesContainer.scrollHeight;

        // Append Typing Indicator
        const typingDiv = document.createElement('div');
        typingDiv.className = 'ai-msg bot-msg typing-indicator';
        typingDiv.style.cssText = 'display: flex; gap: 0.65rem; align-items: flex-start;';
        typingDiv.innerHTML = `
            <div style="width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                <i class="ph ph-sparkle"></i>
            </div>
            <div style="background: var(--bg-tertiary, #2b2b40); padding: 0.65rem 0.95rem; border-radius: 0 14px 14px 14px; font-size: 0.8rem; color: var(--text-muted);">
                Analyzing query...
            </div>
        `;
        aiMessagesContainer.appendChild(typingDiv);
        aiMessagesContainer.scrollTop = aiMessagesContainer.scrollHeight;

        fetch('api/ai_assistant.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: queryText })
        })
            .then(res => res.json())
            .then(data => {
                typingDiv.remove();

                const botMsgDiv = document.createElement('div');
                botMsgDiv.className = 'ai-msg bot-msg';
                botMsgDiv.style.cssText = 'display: flex; gap: 0.65rem; align-items: flex-start;';

                const replyText = data.reply || 'No response received.';
                const formattedReply = formatMarkdown(replyText);

                let cardHtml = '';
                if (data.card_type === 'STAT_SUMMARY' && data.card_data) {
                    const cd = data.card_data;
                    cardHtml = `
                        <div style="margin-top: 0.65rem; background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 12px; padding: 0.75rem; font-size: 0.78rem;">
                            <div style="font-weight: 600; color: #818cf8; margin-bottom: 0.4rem; display: flex; justify-content: space-between;">
                                <span>${escapeHtml(cd.event_name)}</span>
                                <span style="background: #4f46e5; color: white; padding: 0.1rem 0.4rem; border-radius: 8px; font-size: 0.7rem;">${cd.rate}% Attendance</span>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; text-align: center; margin-top: 0.5rem;">
                                <div style="background: rgba(255,255,255,0.05); padding: 0.4rem; border-radius: 8px;">
                                    <div style="font-size: 0.95rem; font-weight: 700; color: #38bdf8;">${cd.total_members}</div>
                                    <div style="font-size: 0.65rem; opacity: 0.8;">Total</div>
                                </div>
                                <div style="background: rgba(34, 197, 94, 0.15); padding: 0.4rem; border-radius: 8px;">
                                    <div style="font-size: 0.95rem; font-weight: 700; color: #4ade80;">${cd.present}</div>
                                    <div style="font-size: 0.65rem; color: #4ade80;">Present</div>
                                </div>
                                <div style="background: rgba(239, 68, 68, 0.15); padding: 0.4rem; border-radius: 8px;">
                                    <div style="font-size: 0.95rem; font-weight: 700; color: #f87171;">${cd.absent}</div>
                                    <div style="font-size: 0.65rem; color: #f87171;">Absent</div>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (data.card_type === 'EMPLOYEE_LIST' && data.card_data) {
                    const cd = data.card_data;
                    let itemsHtml = (cd.items || []).map(item => `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.35rem 0; border-bottom: 1px dashed rgba(255,255,255,0.1);">
                            <div>
                                <strong style="font-size: 0.8rem; color: var(--text-primary);">${escapeHtml(item.name)}</strong>
                                <small style="display: block; font-size: 0.68rem; opacity: 0.7;">${escapeHtml(item.detail)}</small>
                            </div>
                            <span style="font-size: 0.65rem; background: rgba(99,102,241,0.2); color: #818cf8; padding: 0.15rem 0.45rem; border-radius: 10px;">${escapeHtml(item.badge)}</span>
                        </div>
                    `).join('');

                    cardHtml = `
                        <div style="margin-top: 0.65rem; background: rgba(0, 0, 0, 0.2); border: 1px solid var(--border-primary, #2b2b40); border-radius: 12px; padding: 0.65rem;">
                            <div style="font-weight: 600; font-size: 0.78rem; color: #818cf8; margin-bottom: 0.4rem;">${escapeHtml(cd.title)} — <small style="opacity:0.8;">${escapeHtml(cd.subtitle)}</small></div>
                            ${itemsHtml}
                        </div>
                    `;
                }

                let chipsHtml = '';
                if (data.quick_actions && data.quick_actions.length > 0) {
                    chipsHtml = `<div style="display: flex; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.65rem;">` +
                        data.quick_actions.map(action =>
                            `<button type="button" class="ai-chip" onclick="sendAiQuery('${escapeHtml(action.query)}')" style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); color: var(--primary-light, #818cf8); padding: 0.3rem 0.55rem; border-radius: 14px; font-size: 0.7rem; cursor: pointer; transition: all 0.2s;">${escapeHtml(action.label)}</button>`
                        ).join('') + `</div>`;
                }

                botMsgDiv.innerHTML = `
                    <div style="width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                        <i class="ph ph-sparkle"></i>
                    </div>
                    <div style="background: var(--bg-tertiary, #2b2b40); padding: 0.8rem 1rem; border-radius: 0 14px 14px 14px; font-size: 0.85rem; line-height: 1.5; color: var(--text-primary); max-width: 85%; overflow-x: auto;">
                        ${formattedReply}
                        ${cardHtml}
                        ${chipsHtml}
                    </div>
                `;
                aiMessagesContainer.appendChild(botMsgDiv);
                aiMessagesContainer.scrollTop = aiMessagesContainer.scrollHeight;

                speakText(replyText);

                if (data.action_command) {
                    executeActionCommand(data.action_command);
                }
            })
            .catch(err => {
                typingDiv.remove();
                console.error('AI error:', err);
            });
    };

    if (aiChatForm) {
        aiChatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const q = aiQueryInput.value.trim();
            if (q) {
                aiQueryInput.value = '';
                sendAiQuery(q);
            }
        });
    }

    function executeActionCommand(command) {
        if (!command || !command.type) return;

        setTimeout(() => {
            if (command.type === 'NAVIGATE' && command.url) {
                window.location.href = command.url;
            }
            else if (command.type === 'OPEN_MODAL') {
                if (command.modal === 'add_member' && typeof window.openMemberModal === 'function') {
                    window.openMemberModal();
                } else if (command.fallback_url) {
                    window.location.href = command.fallback_url;
                }
            }
            else if (command.type === 'TOGGLE_THEME') {
                const themeBtn = document.getElementById('themeToggle');
                if (themeBtn) themeBtn.click();
            }
            else if (command.type === 'EXPORT' && command.url) {
                window.open(command.url, '_blank');
            }
            else if (command.type === 'FILTER_SEARCH' && command.term) {
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    searchInput.value = command.term;
                    const form = searchInput.closest('form');
                    if (form) form.submit();
                }
            }
        }, 800);
    }

    // =========================================================================
    // GROUP CHAT CONTROLLER LOGIC
    // =========================================================================

    function loadGroupRooms() {
        fetch('api/chat.php?action=get_rooms')
            .then(res => res.json())
            .then(data => {
                if (!data.success || !roomsContainer) return;
                const rooms = data.rooms || [];
                renderRoomsList(rooms);
            })
            .catch(err => console.error('Load rooms error:', err));
    }

    function renderRoomsList(rooms) {
        if (!roomsContainer) return;

        const filterTerm = (roomSearchInput ? roomSearchInput.value : '').toLowerCase().trim();
        const filtered = rooms.filter(r => r.name.toLowerCase().includes(filterTerm));

        if (filtered.length === 0) {
            roomsContainer.innerHTML = `
                <div style="padding: 2rem 1rem; text-align: center; color: var(--text-muted, #94a3b8); font-size: 0.85rem;">
                    No group channels found.
                </div>
            `;
            return;
        }

        roomsContainer.innerHTML = filtered.map(room => {
            const initial = (room.name || 'G').charAt(0).toUpperCase();
            const timeStr = room.last_message_time ? formatChatTime(room.last_message_time) : '';
            const lastMsg = room.last_message ? escapeHtml(room.last_message) : 'No messages yet';
            const sender = room.last_sender ? `${escapeHtml(room.last_sender)}: ` : '';

            return `
                <div class="chat-room-card" onclick="openRoom(${room.id}, '${escapeHtml(room.name)}')" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0.9rem; border-radius: 12px; cursor: pointer; transition: background 0.2s; background: var(--bg-tertiary, #2b2b40); margin-bottom: 0.5rem; border: 1px solid var(--border-primary, #3b3b55);">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; flex-shrink: 0;">
                        ${initial}
                    </div>
                    <div style="flex: 1; overflow: hidden;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.15rem;">
                            <strong style="font-size: 0.85rem; color: var(--text-primary); font-weight: 600;">${escapeHtml(room.name)}</strong>
                            <small style="font-size: 0.68rem; color: var(--text-muted); opacity: 0.8;">${timeStr}</small>
                        </div>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <span style="opacity: 0.7;">${sender}</span>${lastMsg}
                        </p>
                    </div>
                </div>
            `;
        }).join('');
    }

    if (roomSearchInput) {
        roomSearchInput.addEventListener('input', () => loadGroupRooms());
    }

    window.openRoom = function (roomId, roomName) {
        currentRoomId = roomId;
        if (activeRoomTitle) activeRoomTitle.textContent = roomName || 'Group Chat';

        groupRoomListView.style.display = 'none';
        groupActiveChatView.style.display = 'flex';

        fetchRoomMessages(roomId);
    };

    if (btnBackToRooms) {
        btnBackToRooms.addEventListener('click', function () {
            currentRoomId = null;
            groupActiveChatView.style.display = 'none';
            groupRoomListView.style.display = 'flex';
            loadGroupRooms();
        });
    }

    function fetchRoomMessages(roomId) {
        if (!roomId || !groupMessagesContainer) return;

        fetch(`api/chat.php?action=get_messages&room_id=${roomId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                renderMessages(data.messages || []);
            })
            .catch(err => console.error('Fetch messages error:', err));
    }

    function renderMessages(messages) {
        if (!groupMessagesContainer) return;

        const isAtBottom = groupMessagesContainer.scrollHeight - groupMessagesContainer.scrollTop <= groupMessagesContainer.clientHeight + 50;

        groupMessagesContainer.innerHTML = messages.map(msg => {
            const isMine = msg.is_mine;
            const timeStr = formatChatTime(msg.created_at);

            let replyQuoteHtml = '';
            if (msg.reply_message) {
                replyQuoteHtml = `
                    <div style="background: rgba(0,0,0,0.25); border-left: 3px solid #818cf8; padding: 0.3rem 0.5rem; border-radius: 4px; font-size: 0.72rem; margin-bottom: 0.35rem; opacity: 0.9;">
                        <strong>${escapeHtml(msg.reply_sender || 'User')}</strong>: ${escapeHtml(msg.reply_message)}
                    </div>
                `;
            }

            let reactionsHtml = '';
            if (msg.reactions && msg.reactions.length > 0) {
                reactionsHtml = `<div style="display: flex; gap: 0.25rem; flex-wrap: wrap; margin-top: 0.35rem;">` +
                    msg.reactions.map(rx => `
                        <span onclick="toggleReaction(${msg.id}, '${rx.emoji}')" style="background: ${rx.user_reacted ? 'rgba(99,102,241,0.3)' : 'rgba(255,255,255,0.08)'}; border: 1px solid ${rx.user_reacted ? '#6366f1' : 'transparent'}; border-radius: 12px; padding: 0.1rem 0.4rem; font-size: 0.68rem; cursor: pointer; display: flex; align-items: center; gap: 0.2rem;">
                            <span>${rx.emoji}</span> <span style="font-weight: 600;">${rx.count}</span>
                        </span>
                    `).join('') + `</div>`;
            }

            const emojiBarHtml = `
                <div class="msg-reaction-bar" style="display: none; position: absolute; top: -1.4rem; ${isMine ? 'left: 0;' : 'right: 0;'} background: var(--bg-secondary, #1f1f2e); border: 1px solid var(--border-primary, #3b3b55); border-radius: 16px; padding: 0.15rem 0.4rem; gap: 0.3rem; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                    ${['👍', '❤️', '😂', '😮', '😢', '🔥'].map(e => `
                        <span onclick="toggleReaction(${msg.id}, '${e}')" style="cursor: pointer; font-size: 0.85rem; transition: transform 0.15s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">${e}</span>
                    `).join('')}
                </div>
            `;

            return `
                <div class="chat-msg-row ${isMine ? 'mine' : 'other'}" style="display: flex; flex-direction: column; align-items: ${isMine ? 'flex-end' : 'flex-start'}; margin-bottom: 0.75rem; position: relative;" onmouseenter="this.querySelector('.msg-reaction-bar').style.display='flex'" onmouseleave="this.querySelector('.msg-reaction-bar').style.display='none'">
                    ${emojiBarHtml}
                    <div style="font-size: 0.68rem; color: var(--text-muted); margin-bottom: 0.15rem; padding: 0 0.2rem;">
                        <strong>${escapeHtml(msg.sender_name)}</strong> • ${timeStr}
                    </div>
                    <div style="background: ${isMine ? 'linear-gradient(135deg, #4f46e5, #6366f1)' : 'var(--bg-tertiary, #2b2b40)'}; color: white; padding: 0.65rem 0.9rem; border-radius: ${isMine ? '14px 14px 0 14px' : '0 14px 14px 14px'}; font-size: 0.82rem; line-height: 1.4; max-width: 82%; word-break: break-word; border: 1px solid ${isMine ? 'transparent' : 'var(--border-primary, #3b3b55)'};">
                        ${replyQuoteHtml}
                        ${escapeHtml(msg.message)}
                        ${reactionsHtml}
                    </div>
                    <button type="button" onclick="setReplyTarget(${msg.id}, '${escapeHtml(msg.sender_name)}', '${escapeHtml(msg.message)}')" style="background: none; border: none; color: var(--text-muted); font-size: 0.68rem; cursor: pointer; opacity: 0.7; margin-top: 0.15rem;">
                        <i class="ph ph-arrow-u-up-left"></i> Reply
                    </button>
                </div>
            `;
        }).join('');

        if (isAtBottom) {
            groupMessagesContainer.scrollTop = groupMessagesContainer.scrollHeight;
        }
    }

    window.setReplyTarget = function (msgId, senderName, snippet) {
        activeReplyToId = msgId;
        if (replySenderName) replySenderName.textContent = senderName;
        if (replySnippetText) replySnippetText.textContent = snippet;
        if (replyPreviewBar) replyPreviewBar.style.display = 'flex';
        if (groupMessageInput) groupMessageInput.focus();
    };

    if (btnCancelReply) {
        btnCancelReply.addEventListener('click', function () {
            activeReplyToId = null;
            if (replyPreviewBar) replyPreviewBar.style.display = 'none';
        });
    }

    if (groupChatForm) {
        groupChatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const messageText = groupMessageInput.value.trim();
            if (!currentRoomId || !messageText) return;

            const payload = {
                room_id: currentRoomId,
                message: messageText,
                reply_to_id: activeReplyToId
            };

            groupMessageInput.value = '';
            if (replyPreviewBar) replyPreviewBar.style.display = 'none';
            activeReplyToId = null;

            fetch('api/chat.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        fetchRoomMessages(currentRoomId);
                    }
                })
                .catch(err => console.error('Send message error:', err));
        });
    }

    window.toggleReaction = function (messageId, emoji) {
        fetch('api/chat.php?action=add_reaction', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message_id: messageId, emoji: emoji })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && currentRoomId) {
                    fetchRoomMessages(currentRoomId);
                }
            })
            .catch(err => console.error('Reaction error:', err));
    };

    // Create Group Modal Controls
    if (btnOpenCreateGroup && createGroupModal) {
        btnOpenCreateGroup.addEventListener('click', () => {
            createGroupModal.style.display = 'flex';
            if (createGroupInput) createGroupInput.focus();
        });
    }

    if (btnCloseCreateGroup && createGroupModal) {
        btnCloseCreateGroup.addEventListener('click', () => {
            createGroupModal.style.display = 'none';
        });
    }

    if (createGroupForm) {
        createGroupForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const groupName = createGroupInput.value.trim();
            if (!groupName) return;

            fetch('api/chat.php?action=create_room', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: groupName })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        createGroupInput.value = '';
                        createGroupModal.style.display = 'none';
                        loadGroupRooms();
                        if (data.room && data.room.id) {
                            openRoom(data.room.id, data.room.name);
                        }
                    }
                })
                .catch(err => console.error('Create room error:', err));
        });
    }

    // Polling Loop for Group Chat Real-Time Updates
    function startPolling() {
        stopPolling();
        pollingInterval = setInterval(() => {
            if (drawer.style.display === 'flex' && activeTab === 'group') {
                if (currentRoomId) {
                    fetchRoomMessages(currentRoomId);
                } else {
                    loadGroupRooms();
                }
            }
        }, 3000);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    // Helper Functions
    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function formatMarkdown(text) {
        return (text || '')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/_(.*?)_/g, '<em>$1</em>')
            .replace(/\n/g, '<br>');
    }

    function formatChatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
});
