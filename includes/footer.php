            </div><!-- End content-wrapper -->
        </main><!-- End main-content -->
    </div><!-- End app-container -->

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <span>Loading...</span>
        </div>
    </div>

    <!-- JavaScript Base Modules -->
    <script src="assets/js/theme_handler.js?v=2"></script>
    <script src="assets/js/attendance_ajax.js?v=2"></script>
    <script src="assets/js/dashboard_charts.js?v=2"></script>
    
    <script>
        // Initialize sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function openSidebar() {
                if (sidebar) sidebar.classList.add('active');
                if (sidebarOverlay) sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            function closeSidebar() {
                if (sidebar) sidebar.classList.remove('active');
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openSidebar();
                });
            }
            
            if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
            
            // User dropdown
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdownMenu.classList.toggle('active');
                });
                
                document.addEventListener('click', function() {
                    userDropdownMenu.classList.remove('active');
                });
            }
            
            // Force remove active class from loading overlay if present
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay && loadingOverlay.classList.contains('active')) {
                loadingOverlay.classList.remove('active');
            }
            
            // Fix toast container pointer-events
            const toastContainer = document.getElementById('toastContainer');
            if (toastContainer) {
                toastContainer.style.pointerEvents = 'none';
                const observer = new MutationObserver(function() {
                    const hasToasts = toastContainer.querySelector('.toast') !== null;
                    toastContainer.style.pointerEvents = hasToasts ? 'auto' : 'none';
                });
                observer.observe(toastContainer, { childList: true, subtree: true });
            }
        });
    </script>
    
    <!-- Mic Pulse Animation Style -->
    <style>
        @keyframes micPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .mic-active {
            animation: micPulse 1.5s infinite !important;
        }
    </style>

    <!-- Floating AI & Chat Assistant FAB Toggle (Lower Right Corner) -->
    <button id="aiFabToggle" title="Open AI Assistant & Group Chat" style="position: fixed; bottom: 1.5rem; right: 1.5rem; width: 58px; height: 58px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; box-shadow: 0 10px 28px rgba(99, 102, 241, 0.45); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; z-index: 9998; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
        <i class="ph ph-sparkle" id="aiFabIcon"></i>
        <span id="aiFabUnreadBadge" style="display: none; position: absolute; top: -2px; right: -2px; background: #ef4444; color: white; border-radius: 10px; font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.4rem; border: 2px solid white;">•</span>
    </button>

    <!-- Dual-Mode Chat Popup Drawer Container -->
    <div id="aiAssistantDrawer" class="ai-drawer" style="display: none; position: fixed; bottom: 5.5rem; right: 1.5rem; width: 420px; max-width: calc(100vw - 2rem); height: 600px; max-height: 82vh; background: var(--bg-primary, #1e1e2d); box-shadow: 0 20px 50px rgba(0,0,0,0.5); z-index: 9999; flex-direction: column; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--border-primary, #2b2b40); border-radius: 20px; overflow: hidden;">
        
        <!-- Header with Dual Mode Tab Switcher -->
        <div class="ai-drawer-header" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; display: flex; flex-direction: column;">
            <div style="padding: 0.85rem 1.25rem 0.4rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <div style="width: 32px; height: 32px; border-radius: 10px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="ph ph-sparkle"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: white;">AFB Smart Assistant & Chat</h3>
                        <small style="opacity: 0.85; font-size: 0.68rem;">Local AI & Real-Time Messenger</small>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <button type="button" id="aiClearChat" title="Clear Assistant Chat" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="ph ph-trash"></i>
                    </button>
                    <button type="button" id="aiCloseDrawer" title="Close Assistant" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            </div>

            <!-- Tabs selector -->
            <div style="display: flex; background: rgba(0,0,0,0.15); border-top: 1px solid rgba(255,255,255,0.1);">
                <button type="button" id="tabAiBtn" class="chat-tab-btn active" style="flex: 1; padding: 0.6rem; border: none; background: transparent; color: white; font-size: 0.8rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem; border-bottom: 2px solid #818cf8; transition: all 0.2s;">
                    <i class="ph ph-robot"></i> 🤖 AI Assistant
                </button>
                <button type="button" id="tabGroupBtn" class="chat-tab-btn" style="flex: 1; padding: 0.6rem; border: none; background: transparent; color: white; font-size: 0.8rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem; opacity: 0.7; transition: all 0.2s;">
                    <i class="ph ph-users-three"></i> 💬 Group Chat
                </button>
            </div>
        </div>

        <!-- MODE 1: AI ASSISTANT VIEW -->
        <div id="viewAiContent" style="display: flex; flex-direction: column; flex: 1; height: calc(100% - 92px); overflow: hidden;">
            <div id="aiChatMessages" class="ai-chat-body" style="flex: 1; padding: 1rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.85rem;">
                <!-- Welcome message -->
                <div class="ai-msg bot-msg" style="display: flex; gap: 0.65rem; align-items: flex-start;">
                    <div style="width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                        <i class="ph ph-sparkle"></i>
                    </div>
                    <div style="background: var(--bg-tertiary, #2b2b40); padding: 0.8rem 1rem; border-radius: 0 14px 14px 14px; font-size: 0.85rem; line-height: 1.5; color: var(--text-primary); max-width: 85%;">
                        Hello! I am your <strong>AFB Mangaan Smart AI Assistant</strong>. How can I help you with your church attendance and members today?
                    </div>
                </div>

                <!-- Quick Action Chips -->
                <div id="aiQuickChips" style="display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.25rem;">
                    <button type="button" class="ai-chip" onclick="sendAiQuery('Show summary')" style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); color: var(--primary-light, #818cf8); padding: 0.35rem 0.65rem; border-radius: 16px; font-size: 0.72rem; cursor: pointer;">📊 Overview Stats</button>
                    <button type="button" class="ai-chip" onclick="sendAiQuery('Who was absent?')" style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); color: var(--primary-light, #818cf8); padding: 0.35rem 0.65rem; border-radius: 16px; font-size: 0.72rem; cursor: pointer;">📋 Absent Members</button>
                    <button type="button" class="ai-chip" onclick="sendAiQuery('Show at-risk members')" style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); color: var(--primary-light, #818cf8); padding: 0.35rem 0.65rem; border-radius: 16px; font-size: 0.72rem; cursor: pointer;">⚠️ At-Risk Members</button>
                    <button type="button" class="ai-chip" onclick="sendAiQuery('Category breakdown')" style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); color: var(--primary-light, #818cf8); padding: 0.35rem 0.65rem; border-radius: 16px; font-size: 0.72rem; cursor: pointer;">📊 Category Breakdown</button>
                </div>
            </div>

            <!-- Footer input with Web Speech API STT and TTS toggles -->
            <div class="ai-drawer-footer" style="padding: 0.65rem 0.85rem; border-top: 1px solid var(--border-primary, #2b2b40); background: var(--bg-secondary, #151521);">
                <form id="aiChatForm" style="display: flex; gap: 0.4rem; align-items: center;">
                    <button type="button" id="aiMicToggle" title="Voice Dictation (Speech-to-Text)" style="background: var(--bg-tertiary, #2b2b40); border: 1px solid var(--border-primary, #3b3b55); color: var(--text-primary); width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; transition: all 0.2s;">
                        <i class="ph ph-microphone"></i>
                    </button>
                    <button type="button" id="aiSpeakerToggle" title="Toggle Text-to-Speech Output" style="background: var(--bg-tertiary, #2b2b40); border: 1px solid var(--border-primary, #3b3b55); color: var(--text-primary); width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; transition: all 0.2s;">
                        <i class="ph ph-speaker-high"></i>
                    </button>
                    <input type="text" id="aiQueryInput" class="form-control" placeholder="Ask AI or speak..." style="flex: 1; padding: 0.6rem 0.85rem; border-radius: 20px; font-size: 0.82rem; background: var(--bg-tertiary, #2b2b40); color: var(--text-primary); border: 1px solid var(--border-primary, #3b3b55);" autocomplete="off">
                    <button type="submit" id="aiSubmitBtn" class="btn btn-primary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; flex-shrink: 0;">
                        <i class="ph ph-paper-plane-right" style="font-size: 0.95rem;"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- MODE 2: GROUP CHAT MESSENGER VIEW -->
        <div id="viewGroupContent" style="display: none; flex-direction: column; flex: 1; height: calc(100% - 92px); overflow: hidden; position: relative;">
            
            <!-- Sub-View 2A: Room List View -->
            <div id="groupRoomListView" style="display: flex; flex-direction: column; height: 100%; width: 100%;">
                <div style="padding: 0.65rem 0.85rem; border-bottom: 1px solid var(--border-primary, #2b2b40); display: flex; gap: 0.4rem; align-items: center; background: var(--bg-secondary, #151521);">
                    <input type="text" id="groupRoomSearchInput" placeholder="Search channels..." style="flex: 1; padding: 0.45rem 0.75rem; border-radius: 14px; font-size: 0.78rem; background: var(--bg-tertiary, #2b2b40); border: 1px solid var(--border-primary, #3b3b55); color: var(--text-primary);">
                    <button type="button" id="btnOpenCreateGroup" title="Create New Channel" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none; color: white; padding: 0.45rem 0.75rem; border-radius: 14px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-plus"></i> New Group
                    </button>
                </div>

                <div id="groupRoomsContainer" style="flex: 1; padding: 0.75rem; overflow-y: auto;">
                    <!-- Rooms list rendered dynamically -->
                </div>
            </div>

            <!-- Sub-View 2B: Active Room View -->
            <div id="groupActiveChatView" style="display: none; flex-direction: column; height: 100%; width: 100%;">
                <!-- Active Room Header -->
                <div style="padding: 0.6rem 0.85rem; border-bottom: 1px solid var(--border-primary, #2b2b40); display: flex; align-items: center; gap: 0.5rem; background: var(--bg-secondary, #151521);">
                    <button type="button" id="btnBackToRooms" style="background: transparent; border: none; color: var(--text-primary); cursor: pointer; font-size: 1.1rem; display: flex; align-items: center;">
                        <i class="ph ph-arrow-left"></i>
                    </button>
                    <div style="flex: 1; overflow: hidden;">
                        <h4 id="activeRoomTitle" style="margin: 0; font-size: 0.88rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Group Chat</h4>
                    </div>
                </div>

                <!-- Messages area -->
                <div id="groupMessagesContainer" style="flex: 1; padding: 0.85rem; overflow-y: auto; display: flex; flex-direction: column;">
                    <!-- Messages rendered dynamically -->
                </div>

                <!-- Reply Preview Bar -->
                <div id="replyPreviewBar" style="display: none; padding: 0.4rem 0.85rem; background: rgba(99,102,241,0.15); border-top: 1px solid rgba(99,102,241,0.3); align-items: center; justify-content: space-between; font-size: 0.72rem;">
                    <div style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis; padding-right: 0.5rem;">
                        <span style="color: #818cf8; font-weight: 600;">Replying to <span id="replySenderName">User</span>:</span>
                        <span id="replySnippetText" style="opacity: 0.8; margin-left: 0.2rem;">Text snippet...</span>
                    </div>
                    <button type="button" id="btnCancelReply" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.9rem;">
                        <i class="ph ph-x"></i>
                    </button>
                </div>

                <!-- Active Chat Footer Input Form -->
                <div class="ai-drawer-footer" style="padding: 0.65rem 0.85rem; border-top: 1px solid var(--border-primary, #2b2b40); background: var(--bg-secondary, #151521);">
                    <form id="groupChatForm" style="display: flex; gap: 0.4rem; align-items: center;">
                        <input type="text" id="groupMessageInput" class="form-control" placeholder="Type a message..." style="flex: 1; padding: 0.6rem 0.85rem; border-radius: 20px; font-size: 0.82rem; background: var(--bg-tertiary, #2b2b40); color: var(--text-primary); border: 1px solid var(--border-primary, #3b3b55);" autocomplete="off">
                        <button type="submit" class="btn btn-primary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none; flex-shrink: 0;">
                            <i class="ph ph-paper-plane-right" style="font-size: 0.95rem;"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Create Group Inline Modal Overlay -->
            <div id="createGroupModal" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.75); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
                <div style="background: var(--bg-primary, #1e1e2d); border: 1px solid var(--border-primary, #3b3b55); border-radius: 16px; padding: 1.25rem; width: 100%; max-width: 320px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                    <h4 style="margin: 0 0 0.85rem 0; font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Create New Group Room</h4>
                    <form id="createGroupForm">
                        <input type="text" id="createGroupNameInput" class="form-control" placeholder="Group Room Name" style="width: 100%; padding: 0.6rem 0.85rem; border-radius: 10px; font-size: 0.82rem; background: var(--bg-tertiary, #2b2b40); color: var(--text-primary); border: 1px solid var(--border-primary, #3b3b55); margin-bottom: 1rem;" required>
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <button type="button" id="btnCloseCreateGroup" style="background: var(--bg-tertiary, #2b2b40); border: 1px solid var(--border-primary, #3b3b55); color: var(--text-primary); padding: 0.45rem 0.85rem; border-radius: 10px; font-size: 0.78rem; cursor: pointer;">Cancel</button>
                            <button type="submit" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none; color: white; padding: 0.45rem 0.85rem; border-radius: 10px; font-size: 0.78rem; font-weight: 600; cursor: pointer;">Create</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Script Import for Dual-Mode Chat Widget -->
    <script src="assets/js/ai_chat_widget.js?v=2"></script>
    
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
