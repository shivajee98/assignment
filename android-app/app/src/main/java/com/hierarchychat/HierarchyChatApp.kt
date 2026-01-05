package com.hierarchychat

import android.app.Application
import com.hierarchychat.utils.SessionManager

class HierarchyChatApp : Application() {
    
    override fun onCreate() {
        super.onCreate()
        SessionManager.init(this)
    }
}
