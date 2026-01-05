package com.hierarchychat

import android.app.Application

class HierarchyChatApp : Application() {
    
    companion object {
        lateinit var instance: HierarchyChatApp
            private set
    }
    
    override fun onCreate() {
        super.onCreate()
        instance = this
    }
}
