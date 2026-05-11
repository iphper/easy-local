package main

import (
	_ "easy-local/app"
	"easy-local/core/application"
)

func init() {

	// 初始化应用
	application.Instance()

}

func main() {
	// 运行应用
	application.Instance().Run()
}
